<?php

namespace App\Http\Controllers;

use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\ShopVisit;
use App\Models\UserAddress;
use App\Services\CartService;
use App\Services\DualChannelMailService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cartService,
        protected DualChannelMailService $mailService
    ) {}

    public function index(Request $request): View|RedirectResponse
    {
        if (! Auth::check()) {
            return redirect()->route('auth.index')
                ->with('info', 'Sign in or create your customer account to complete checkout while keeping your bag intact.');
        }

        $cart = $this->cartService->getActiveCart($request, false);
        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('menu.index')
                ->with('info', 'Your bag is currently empty—pour a drink or pick coastal merch first.');
        }

        $user = Auth::user();
        $addresses = $user->addresses()->orderByDesc('is_default')->get();
        $defaultZone = $addresses->first()?->delivery_zone ?? 'local_express';
        $summary = $this->cartService->summarize($cart, $defaultZone);

        return view('storefront.checkout', [
            'user' => $user,
            'addresses' => $addresses,
            'summary' => $summary,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user();
        $cart = $this->cartService->getActiveCart($request, false);

        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('menu.index')->with('error', 'Your shopping bag is empty.');
        }

        $validated = $request->validate([
            'billing_name' => ['required', 'string', 'max:120'],
            'billing_email' => ['required', 'email', 'max:150'],
            'billing_phone' => ['required', 'string', 'max:32'],
            'saved_address_id' => ['nullable', 'integer'],
            'shipping_recipient' => ['required_without:saved_address_id', 'nullable', 'string', 'max:120'],
            'shipping_phone' => ['required_without:saved_address_id', 'nullable', 'string', 'max:32'],
            'shipping_address_line' => ['required_without:saved_address_id', 'nullable', 'string', 'max:255'],
            'shipping_city' => ['required_without:saved_address_id', 'nullable', 'string', 'max:120'],
            'shipping_province' => ['required_without:saved_address_id', 'nullable', 'string', 'max:120'],
            'shipping_postal_code' => ['required_without:saved_address_id', 'nullable', 'string', 'max:20'],
            'delivery_zone' => ['required', 'in:local_express,provincial_parcel'],
            'payment_method' => ['required', 'in:gcash,maya_card,cod'],
            'payment_account_ref' => ['nullable', 'string', 'max:80'],
            'order_notes' => ['nullable', 'string', 'max:500'],
            'save_address' => ['nullable', 'boolean'],
        ]);

        $shipping = [
            'recipient' => $validated['shipping_recipient'] ?? '',
            'phone' => $validated['shipping_phone'] ?? '',
            'address_line' => $validated['shipping_address_line'] ?? '',
            'city' => $validated['shipping_city'] ?? '',
            'province' => $validated['shipping_province'] ?? '',
            'postal_code' => $validated['shipping_postal_code'] ?? '',
            'zone' => $validated['delivery_zone'],
        ];

        if (! empty($validated['saved_address_id'])) {
            $address = UserAddress::where('user_id', $user->id)
                ->findOrFail((int) $validated['saved_address_id']);
            $shipping = [
                'recipient' => $address->recipient_name,
                'phone' => $address->phone,
                'address_line' => trim($address->street_address.($address->barangay ? ', '.$address->barangay : '')),
                'city' => $address->city,
                'province' => $address->province,
                'postal_code' => $address->postal_code,
                'zone' => $address->delivery_zone,
            ];
        } elseif ($request->boolean('save_address')) {
            UserAddress::create([
                'user_id' => $user->id,
                'label' => 'Checkout Address',
                'recipient_name' => $shipping['recipient'],
                'phone' => $shipping['phone'],
                'street_address' => $shipping['address_line'],
                'city' => $shipping['city'],
                'province' => $shipping['province'],
                'postal_code' => $shipping['postal_code'],
                'delivery_zone' => $shipping['zone'],
                'is_default' => $user->addresses()->count() === 0,
            ]);
        }

        $hasPerishables = $cart->items->contains(fn ($item) => (bool) $item->variant->product->is_perishable);
        if ($hasPerishables && $shipping['zone'] === 'provincial_parcel') {
            return back()
                ->withInput()
                ->with('error', 'Fresh café beverages require Local Express Rider delivery so drinks arrive chilled or piping hot. Switch delivery zone to Local Rider Express or remove drinks for provincial parcel shipping.');
        }

        try {
            $order = DB::transaction(function () use ($cart, $user, $validated, $shipping) {
                $subtotal = 0.0;
                $verifiedItems = [];

                foreach ($cart->items as $item) {
                    $variant = ProductVariant::with('product')->lockForUpdate()->findOrFail($item->product_variant_id);
                    if (! $variant->is_active || $variant->product->is_archived) {
                        throw new \RuntimeException($variant->product->name.' is no longer available.');
                    }
                    if ($variant->stock_qty < $item->quantity) {
                        throw new \RuntimeException('Insufficient stock for '.$variant->product->name.' ('.$variant->label.'). Available: '.$variant->stock_qty);
                    }

                    $unitPrice = (float) $variant->price;
                    $lineTotal = round($unitPrice * $item->quantity, 2);
                    $subtotal += $lineTotal;

                    $variant->decrement('stock_qty', $item->quantity);

                    $verifiedItems[] = [
                        'product_id' => $variant->product_id,
                        'variant_id' => $variant->id,
                        'name' => $variant->product->name,
                        'label' => $variant->label,
                        'is_perishable' => (bool) $variant->product->is_perishable,
                        'quantity' => $item->quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ];
                }

                $coupon = $cart->coupon;
                $discount = $coupon ? $coupon->computeDiscount($subtotal) : 0.0;
                $afterDiscount = max(0.0, $subtotal - $discount);

                $deliveryFee = $afterDiscount >= CartService::FREE_DELIVERY_THRESHOLD
                    ? 0.0
                    : ($shipping['zone'] === 'provincial_parcel' ? CartService::PROVINCIAL_PARCEL_FEE : CartService::LOCAL_EXPRESS_FEE);

                if ($coupon && strtoupper($coupon->code) === 'FREESIP' && $shipping['zone'] === 'local_express') {
                    $deliveryFee = 0.0;
                    $discount = 0.0;
                    $afterDiscount = $subtotal;
                }

                $grandTotal = round($afterDiscount + $deliveryFee, 2);

                $orderNumber = 'CI-'.now()->format('Y').'-'.strtoupper(Str::random(5));

                $order = Order::create([
                    'order_number' => $orderNumber,
                    'user_id' => $user->id,
                    'coupon_id' => $coupon?->id,
                    'billing_name' => $validated['billing_name'],
                    'billing_email' => $validated['billing_email'],
                    'billing_phone' => $validated['billing_phone'],
                    'shipping_recipient' => $shipping['recipient'],
                    'shipping_phone' => $shipping['phone'],
                    'shipping_address_line' => $shipping['address_line'],
                    'shipping_city' => $shipping['city'],
                    'shipping_province' => $shipping['province'],
                    'shipping_postal_code' => $shipping['postal_code'],
                    'delivery_zone' => $shipping['zone'],
                    'subtotal' => $subtotal,
                    'discount_amount' => $discount,
                    'delivery_fee' => $deliveryFee,
                    'grand_total' => $grandTotal,
                    'status' => 'pending',
                    'order_notes' => $validated['order_notes'] ?? null,
                    'placed_at' => now(),
                ]);

                foreach ($verifiedItems as $line) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $line['product_id'],
                        'product_variant_id' => $line['variant_id'],
                        'product_name_snapshot' => $line['name'],
                        'variant_label_snapshot' => $line['label'],
                        'is_perishable' => $line['is_perishable'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'line_total' => $line['line_total'],
                    ]);
                }

                $isPrepaid = in_array($validated['payment_method'], ['gcash', 'maya_card'], true);
                Payment::create([
                    'order_id' => $order->id,
                    'method' => $validated['payment_method'],
                    'reference_number' => $validated['payment_account_ref'] ?: 'SIM-'.strtoupper(Str::random(8)),
                    'amount' => $grandTotal,
                    'status' => $isPrepaid ? 'paid' : 'pending',
                    'paid_at' => $isPrepaid ? now() : null,
                ]);

                Delivery::create([
                    'order_id' => $order->id,
                    'tracking_number' => 'TRK-'.strtoupper(Str::random(6)).'-ISLA',
                    'carrier_name' => $shipping['zone'] === 'local_express' ? 'Isla Express Rider' : 'Archipelago Courier Parcel',
                    'status' => 'unassigned',
                ]);

                $cart->update(['status' => 'converted']);
                $cart->items()->delete();

                ShopVisit::where('session_token', $cart->session_token)
                    ->whereNull('converted_order_id')
                    ->update(['converted_order_id' => $order->id, 'had_cart' => true]);

                return $order;
            });
        } catch (\RuntimeException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $order->load(['items', 'payment', 'delivery']);
        $this->mailService->sendOrderConfirmation($order);

        return redirect()
            ->route('account.index')
            ->with('success', 'Order #'.$order->order_number.' confirmed! Check your Email Inbox drawer for your digital receipt.');
    }
}
