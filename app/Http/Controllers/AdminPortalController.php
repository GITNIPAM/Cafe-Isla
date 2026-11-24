<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\IslandSeries;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ReturnRequest;
use App\Models\ShopVisit;
use App\Models\User;
use App\Services\DualChannelMailService;
use App\Services\OrderReturnService;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use League\Flysystem\FilesystemException;

class AdminPortalController extends Controller
{
    public function __construct(
        protected DualChannelMailService $mailService,
        protected OrderReturnService $orderReturns,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $user->role;

        $defaultTab = match ($role) {
            'courier' => 'orders',
            default => 'analytics',
        };

        $activeTab = $request->query('tab', $defaultTab);
        if ($role === 'courier') {
            $activeTab = 'orders';
        }

        // Analytics Data (Admin)
        $paidRevenue = (float) Payment::whereNotNull('paid_at')
            ->whereIn('status', ['paid', 'refunded'])
            ->sum(DB::raw('amount - refunded_amount'));
        $totalOrders = Order::count();
        $pendingOrdersCount = Order::whereIn('status', ['pending', 'confirmed', 'preparing'])->count();
        $lowStockVariants = ProductVariant::with('product.islandSeries')
            ->where('is_active', true)
            ->whereColumn('stock_qty', '<=', 'low_stock_threshold')
            ->orderBy('stock_qty')
            ->get();

        $topProducts = OrderItem::select('product_name_snapshot', DB::raw('SUM(quantity) as units_sold'), DB::raw('SUM(line_total) as gross_sales'))
            ->whereHas('order', fn ($q) => $q->whereNotIn('status', ['cancelled', 'returned']))
            ->groupBy('product_name_snapshot')
            ->orderByDesc('units_sold')
            ->limit(6)
            ->get();

        $totalVisits = max(1, ShopVisit::count());
        $cartVisits = ShopVisit::where('had_cart', true)->count();
        $convertedVisits = ShopVisit::whereNotNull('converted_order_id')->count();
        $conversionRate = round(($convertedVisits / $totalVisits) * 100, 1);
        $abandonmentRate = $cartVisits > 0
            ? round((($cartVisits - $convertedVisits) / $cartVisits) * 100, 1)
            : 0.0;

        // Orders & Couriers
        $ordersQuery = Order::with(['user', 'items.product', 'payment', 'delivery.courier', 'returnRequests'])
            ->orderByDesc('placed_at');

        if ($role === 'courier') {
            $ordersQuery->whereHas('delivery', function ($q) use ($user) {
                $q->where('courier_user_id', $user->id);
            });
        }

        $orders = $ordersQuery->get();
        $couriers = User::where('role', 'courier')->where('is_active', true)->orderBy('name')->get();

        // Inventory Data
        $products = Product::with(['islandSeries', 'category', 'variants'])->orderBy('id', 'desc')->get();
        $islands = IslandSeries::orderBy('sort_order')->get();
        $categories = Category::orderBy('sort_order')->get();

        // Return Requests
        $returns = ReturnRequest::with(['order', 'orderItem.product', 'user', 'processor'])
            ->latest('created_at')
            ->get();

        // Team & Customers (Admin only)
        $customers = User::where('role', 'customer')
            ->with([
                'orders' => fn ($q) => $q->orderByDesc('placed_at'),
                'orders.items',
                'returnRequests.order',
                'addresses',
            ])
            ->withCount('orders')
            ->withSum(['orders' => fn ($q) => $q->whereNotIn('status', ['cancelled', 'returned'])], 'grand_total')
            ->orderBy('name')
            ->get();

        $teamMembers = User::whereIn('role', ['admin', 'courier'])
            ->orderBy('role')
            ->orderBy('name')
            ->get();

        return view('admin.index', [
            'user' => $user,
            'role' => $role,
            'activeTab' => $activeTab,
            'metrics' => [
                'revenue' => $paidRevenue,
                'orders' => $totalOrders,
                'pending_orders' => $pendingOrdersCount,
                'low_stock_count' => $lowStockVariants->count(),
                'total_visits' => $totalVisits,
                'cart_visits' => $cartVisits,
                'converted_visits' => $convertedVisits,
                'conversion_rate' => $conversionRate,
                'abandonment_rate' => $abandonmentRate,
            ],
            'lowStockVariants' => $lowStockVariants,
            'topProducts' => $topProducts,
            'orders' => $orders,
            'couriers' => $couriers,
            'products' => $products,
            'islands' => $islands,
            'categories' => $categories,
            'returns' => $returns,
            'customers' => $customers,
            'teamMembers' => $teamMembers,
        ]);
    }

    public function storeProduct(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'island_series_id' => ['required', 'integer', 'exists:island_series,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'sku' => ['required', 'string', 'max:40', 'unique:products,sku'],
            'name' => ['required', 'string', 'max:150'],
            'short_excerpt' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'tasting_notes' => ['nullable', 'string', 'max:255'],
            'is_perishable' => ['required', 'boolean'],
            'base_price' => ['required', 'numeric', 'min:1'],
            'variant_label' => ['required', 'string', 'max:80'],
            'serve_temp' => ['required', 'in:hot,iced,na'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $imagePath = 'assets/images/editorial/menu-table-ritual.jpg';
        if ($request->hasFile('image')) {
            $imagePath = $this->storeProductImage($request->file('image'));
        }

        $slug = Str::slug($validated['name']);
        if (Product::where('slug', $slug)->exists()) {
            $slug .= '-'.strtolower(Str::random(4));
        }

        $product = Product::create([
            'island_series_id' => $validated['island_series_id'],
            'category_id' => $validated['category_id'],
            'sku' => strtoupper(trim($validated['sku'])),
            'name' => $validated['name'],
            'slug' => $slug,
            'short_excerpt' => $validated['short_excerpt'],
            'description' => $validated['description'],
            'tasting_notes' => $validated['tasting_notes'] ?? null,
            'is_perishable' => (bool) $validated['is_perishable'],
            'base_price' => $validated['base_price'],
            'image_path' => $imagePath,
            'is_featured' => false,
            'is_archived' => false,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'variant_code' => strtoupper(trim($validated['sku'])).'-V1',
            'label' => $validated['variant_label'],
            'serve_temp' => $validated['serve_temp'],
            'size_option' => $validated['variant_label'],
            'price' => $validated['base_price'],
            'stock_qty' => $validated['stock_qty'],
            'low_stock_threshold' => 5,
            'is_active' => true,
        ]);

        return redirect()->route('admin.index', ['tab' => 'inventory'])->with('success', 'Product "'.$product->name.'" created in catalog.');
    }

    public function updateProduct(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'island_series_id' => ['required', 'integer', 'exists:island_series,id'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:150'],
            'short_excerpt' => ['required', 'string', 'max:255'],
            'base_price' => ['required', 'numeric', 'min:1'],
            'is_archived' => ['nullable', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ]);

        $imagePath = $product->image_path;
        if ($request->hasFile('image')) {
            $imagePath = $this->storeProductImage($request->file('image'));
        }

        $product->update([
            'island_series_id' => $validated['island_series_id'],
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'short_excerpt' => $validated['short_excerpt'],
            'base_price' => $validated['base_price'],
            'is_archived' => $request->boolean('is_archived'),
            'image_path' => $imagePath,
        ]);

        return redirect()->route('admin.index', ['tab' => 'inventory'])->with('success', 'Updated "'.$product->name.'".');
    }

    public function storeVariant(Request $request, Product $product): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:80'],
            'serve_temp' => ['required', 'in:hot,iced,na'],
            'price' => ['required', 'numeric', 'min:1'],
            'stock_qty' => ['required', 'integer', 'min:0'],
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'variant_code' => $product->sku.'-'.strtoupper(Str::random(4)),
            'label' => $validated['label'],
            'serve_temp' => $validated['serve_temp'],
            'size_option' => $validated['label'],
            'price' => $validated['price'],
            'stock_qty' => $validated['stock_qty'],
            'low_stock_threshold' => 5,
            'is_active' => true,
        ]);

        return redirect()->route('admin.index', ['tab' => 'inventory'])->with('success', 'Added variant "'.$validated['label'].'" to '.$product->name.'.');
    }

    public function updateVariant(Request $request, ProductVariant $variant): RedirectResponse
    {
        $validated = $request->validate([
            'price' => ['required', 'numeric', 'min:1'],
            'stock_qty' => ['required', 'integer', 'min:0'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $variant->update([
            'price' => $validated['price'],
            'stock_qty' => $validated['stock_qty'],
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $variant->is_active,
        ]);

        return redirect()->route('admin.index', ['tab' => 'inventory'])->with('success', 'Stock and price updated for '.$variant->product->name.' ('.$variant->label.').');
    }

    private function storeProductImage(UploadedFile $file): string
    {
        try {
            $name = Storage::disk('catalog_uploads')->putFile('', $file);
        } catch (FilesystemException $exception) {
            report($exception);

            throw ValidationException::withMessages(['image' => 'The upload folder is unavailable. Ask the site administrator to check its write permissions.']);
        }

        if ($name === false) {
            throw ValidationException::withMessages(['image' => 'The image could not be saved.']);
        }

        return 'uploads/products/'.$name;
    }

    public function deleteProduct(Product $product): RedirectResponse
    {
        if ($product->orderItems()->exists()) {
            $product->update(['is_archived' => true]);

            return redirect()->route('admin.index', ['tab' => 'inventory'])->with('info', '"'.$product->name.'" has order history so it was archived instead of permanently deleted.');
        }

        $name = $product->name;
        $product->delete();

        return redirect()->route('admin.index', ['tab' => 'inventory'])->with('success', 'Deleted "'.$name.'" permanently.');
    }

    public function storeCategory(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'product_type' => ['required', 'in:drink,merch'],
            'description' => ['nullable', 'string', 'max:300'],
        ]);

        Category::create([
            'name' => $validated['name'],
            'slug' => Str::slug($validated['name']).'-'.strtolower(Str::random(3)),
            'product_type' => $validated['product_type'],
            'description' => $validated['description'] ?? null,
            'sort_order' => Category::count() + 1,
            'is_archived' => false,
        ]);

        return redirect()->route('admin.index', ['tab' => 'inventory'])->with('success', 'Category "'.$validated['name'].'" added.');
    }

    public function updateCategory(Request $request, Category $category): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'product_type' => ['required', 'in:drink,merch'],
            'is_archived' => ['nullable', 'boolean'],
        ]);

        $category->update([
            'name' => $validated['name'],
            'product_type' => $validated['product_type'],
            'is_archived' => $request->boolean('is_archived'),
        ]);

        return redirect()->route('admin.index', ['tab' => 'inventory'])->with('success', 'Category "'.$category->name.'" updated.');
    }

    public function updateOrderStatus(Request $request, Order $order): RedirectResponse
    {
        $user = $request->user();
        $isCourier = $user->role === 'courier';
        $validated = $request->validate([
            'status' => ['required', 'in:confirmed,preparing,shipped,delivered,cancelled'],
            'delivery_status' => ['nullable', 'in:assigned,in_transit,delivered'],
            'courier_user_id' => ['nullable', 'integer'],
            'proof_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $newStatus = $validated['status'];
        if ($newStatus === 'cancelled') {
            abort_if($isCourier, 403);
            try {
                $this->orderReturns->cancel($order, 'The café cancelled this order before preparation.', $user);
            } catch (DomainException $e) {
                return redirect()->route('admin.index', ['tab' => 'orders'])->with('error', $e->getMessage());
            }

            return redirect()->route('admin.index', ['tab' => 'orders'])
                ->with('success', 'Order #'.$order->order_number.' cancelled. The customer was notified.');
        }

        [$order, $deliveryStatus] = DB::transaction(function () use ($order, $validated, $newStatus, $user, $isCourier) {
            $order = Order::lockForUpdate()->findOrFail($order->id);
            $delivery = $order->delivery;
            if ($isCourier) {
                abort_unless((int) $delivery?->courier_user_id === $user->id, 403);
                abort_unless(in_array($newStatus, ['shipped', 'delivered'], true), 403);
            }

            $deliveryStatus = match ([$order->status, $newStatus]) {
                ['pending', 'confirmed'], ['confirmed', 'preparing'] => null,
                ['preparing', 'shipped'] => 'assigned',
                ['shipped', 'shipped'] => $order->nextCourierStatus() === 'shipped' ? 'in_transit' : false,
                ['shipped', 'delivered'] => $order->nextCourierStatus() === 'delivered' ? 'delivered' : false,
                default => false,
            };

            if ($deliveryStatus === false
                || ($deliveryStatus && ! $delivery)
                || ($isCourier && $order->nextCourierStatus() !== $newStatus)
                || (isset($validated['delivery_status']) && $validated['delivery_status'] !== $deliveryStatus)) {
                throw ValidationException::withMessages(['status' => 'Use the next available fulfillment step for this order.']);
            }

            $deliveryUpdate = [];
            if ($deliveryStatus === 'assigned') {
                $courierId = $validated['courier_user_id'] ?? $delivery->courier_user_id;
                if (! $courierId || ! User::whereKey($courierId)->where('role', 'courier')->where('is_active', true)->exists()) {
                    throw ValidationException::withMessages(['courier_user_id' => 'Choose an active courier to ship this order.']);
                }
                $deliveryUpdate['courier_user_id'] = $courierId;
            } elseif (isset($validated['courier_user_id'])) {
                throw ValidationException::withMessages(['courier_user_id' => 'A courier is assigned when the order ships.']);
            }

            if ($deliveryStatus) {
                $deliveryUpdate['status'] = $deliveryStatus;
                if ($deliveryStatus === 'in_transit') {
                    $deliveryUpdate['shipped_at'] = $delivery->shipped_at ?? now();
                } elseif ($deliveryStatus === 'delivered') {
                    $deliveryUpdate['delivered_at'] = now();
                    if ($order->payment?->amountToCollect() > 0) {
                        $order->payment->update(['status' => 'paid', 'paid_at' => now()]);
                    }
                }
            }
            if (isset($validated['proof_notes'])) {
                $deliveryUpdate['proof_notes'] = $validated['proof_notes'];
            }
            if ($deliveryUpdate && $delivery) {
                $delivery->update($deliveryUpdate);
            }
            $order->update(['status' => $newStatus]);

            return [$order, $deliveryStatus];
        }, 3);

        $this->mailService->sendShippingUpdate($order->loadMissing('delivery.courier'));

        $stageLabel = match ($newStatus) {
            'shipped' => $deliveryStatus === 'assigned' ? 'Shipped (Courier Assigned)' : 'Courier Tracking (In Transit)',
            'delivered' => 'Delivered (Courier Confirmed)',
            default => ucfirst($newStatus),
        };

        return redirect()->route('admin.index', ['tab' => 'orders'])->with('success', 'Order #'.$order->order_number.' updated to '.$stageLabel.'.');
    }

    public function processReturn(Request $request, ReturnRequest $returnRequest): RedirectResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'in:approved,rejected'],
            'admin_remarks' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $this->orderReturns->process($returnRequest, $request->user(), $validated['decision'], $validated['admin_remarks'] ?? null);
        } catch (DomainException $e) {
            return redirect()->route('admin.index', ['tab' => 'returns'])->with('error', $e->getMessage());
        }

        return redirect()->route('admin.index', ['tab' => 'returns'])->with('success', 'Return #'.$returnRequest->id.' marked '.ucfirst($validated['decision']).'.');
    }

    public function updateUserRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'role' => ['required', 'in:customer,admin,courier'],
            'is_active' => ['required', 'boolean'],
        ]);

        $user->update($validated);

        return redirect()->route('admin.index', ['tab' => 'team'])->with('success', 'Permissions updated for '.$user->name.'.');
    }

    public function storeTeamMember(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32'],
            'role' => ['required', 'in:admin,courier'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => strtolower(trim($validated['email'])),
            'phone' => $validated['phone'] ?? null,
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
            'is_active' => true,
        ]);

        return redirect()->route('admin.index', ['tab' => 'team'])->with('success', 'Team member '.$validated['name'].' added as '.ucfirst($validated['role']).'.');
    }
}
