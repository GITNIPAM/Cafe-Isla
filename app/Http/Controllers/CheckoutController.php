<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Delivery;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\ProductVariant;
use App\Models\ShopVisit;
use App\Models\UserAddress;
use App\Services\CartService;
use App\Services\DualChannelMailService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        $cart = $this->cartService->getActiveCart($request, false);
        if (! $cart || $cart->items->isEmpty()) {
            return redirect()->route('menu.index')
                ->with('info', 'Your bag is currently empty—pour a drink or pick coastal merch first.');
        }

        $user = $request->user();
        $addresses = $user->addresses()->orderByDesc('is_default')->get();
        $selectedAddressId = old('saved_address_id', $addresses->firstWhere('is_default', true)?->id);
        $address = is_numeric($selectedAddressId) ? $addresses->find($selectedAddressId) : null;
        $zone = old('delivery_zone', $address?->delivery_zone ?? 'local_express');
        $zone = $zone === 'provincial_parcel' ? 'provincial_parcel' : 'local_express';
        $summary = $this->cartService->summarize($cart, $zone, currentPrices: true);
        if ($summary['has_perishables'] && ! $request->session()->hasOldInput('delivery_zone')) {
            $zone = 'local_express';
        }
        $deliveryError = $this->deliveryError(
            $address?->city ?? (string) old('shipping_city', ''),
            $address?->province ?? (string) old('shipping_province', ''),
            $zone,
            $summary['has_perishables'],
        );

        return view('storefront.checkout', [
            'user' => $user,
            'addresses' => $addresses,
            'selectedAddressId' => $selectedAddressId,
            'selectedZone' => $zone,
            'deliveryError' => $deliveryError,
            'summary' => $summary,
        ]);
    }

    public function quote(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'saved_address_id' => ['nullable', 'integer'],
            'shipping_city' => ['nullable', 'string', 'max:120'],
            'shipping_province' => ['nullable', 'string', 'max:120'],
            'delivery_zone' => ['required', 'in:local_express,provincial_parcel'],
        ]);

        $cart = $this->cartService->getActiveCart($request, false);
        if (! $cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Your shopping bag is empty.'], 422);
        }

        $address = ! empty($validated['saved_address_id'])
            ? $request->user()->addresses()->findOrFail($validated['saved_address_id']) : null;
        $zone = $validated['delivery_zone'];
        $summary = $this->cartService->summarize($cart, $zone, currentPrices: true);
        $error = $this->deliveryError(
            $address?->city ?? $validated['shipping_city'] ?? '',
            $address?->province ?? $validated['shipping_province'] ?? '',
            $zone,
            $summary['has_perishables'],
        );

        return response()->json([
            'delivery_error' => $error,
            'subtotal' => $summary['subtotal'],
            'discount_amount' => $summary['discount_amount'],
            'delivery_fee' => $summary['delivery_fee'],
            'grand_total' => $summary['grand_total'],
            'coupon_code' => $summary['coupon_code'],
            'items' => collect($summary['items'])->mapWithKeys(fn ($item) => [
                $item['id'] => ['quantity' => $item['quantity'], 'line_total' => $item['line_total']],
            ]),
        ])->header('Cache-Control', 'private, no-store');
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
            'payment_method' => ['required', 'in:gcash,cod'],
            'payment_account_ref' => ['nullable', 'string', 'max:80'],
            'order_notes' => ['nullable', 'string', 'max:500'],
            'save_address' => ['nullable', 'boolean'],
            'quoted_total' => ['sometimes', 'required', 'numeric', 'min:0'],
        ], [
            'quoted_total.required' => 'Review the updated amount before placing your order.',
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
                'zone' => $validated['delivery_zone'],
            ];
        }

        try {
            $order = DB::transaction(function () use ($cart, $user, $validated, $shipping) {
                $cart = Cart::lockForUpdate()->findOrFail($cart->id);
                $items = $cart->items()->orderBy('product_variant_id')->get();
                if ($cart->status !== 'active' || $items->isEmpty()) {
                    throw new DomainException('This bag has already been checked out or is empty.');
                }

                $subtotal = 0.0;
                $verifiedItems = [];
                $hasPerishables = false;

                foreach ($items as $item) {
                    $variant = ProductVariant::with('product')->lockForUpdate()->findOrFail($item->product_variant_id);
                    if (! $variant->is_active || $variant->product->is_archived) {
                        throw new DomainException($variant->product->name.' is no longer available.');
                    }
                    if ($variant->stock_qty < $item->quantity) {
                        throw new DomainException('Insufficient stock for '.$variant->product->name.' ('.$variant->label.'). Available: '.$variant->stock_qty);
                    }

                    $unitPrice = (float) $variant->price;
                    $lineTotal = round($unitPrice * $item->quantity, 2);
                    $subtotal += $lineTotal;
                    $hasPerishables = $hasPerishables || $variant->product->is_perishable;

                    $variant->decrement('stock_qty', $item->quantity);

                    $verifiedItems[] = [
                        'product_id' => $variant->product_id,
                        'variant_id' => $variant->id,
                        'name' => $variant->product->name,
                        'label' => $variant->label,
                        'custom_notes' => $item->custom_notes,
                        'is_perishable' => (bool) $variant->product->is_perishable,
                        'quantity' => $item->quantity,
                        'unit_price' => $unitPrice,
                        'line_total' => $lineTotal,
                    ];
                }

                if ($error = $this->deliveryError($shipping['city'], $shipping['province'], $shipping['zone'], $hasPerishables)) {
                    throw new DomainException($error);
                }

                $totals = $this->cartService->calculateTotals(round($subtotal, 2), $cart->coupon, $shipping['zone']);
                $grandTotal = $totals['grand_total'];
                if (isset($validated['quoted_total']) && round((float) $validated['quoted_total'], 2) !== $grandTotal) {
                    throw new DomainException('Your bag total changed. Review the updated amount before placing your order.');
                }

                if (empty($validated['saved_address_id']) && ! empty($validated['save_address'])) {
                    $user->addresses()->create([
                        'label' => 'Checkout Address',
                        'recipient_name' => $shipping['recipient'],
                        'phone' => $shipping['phone'],
                        'street_address' => $shipping['address_line'],
                        'city' => $shipping['city'],
                        'province' => $shipping['province'],
                        'postal_code' => $shipping['postal_code'],
                        'delivery_zone' => $shipping['zone'],
                        'is_default' => ! $user->addresses()->exists(),
                    ]);
                }

                $orderNumber = 'CI-'.now()->format('Y').'-'.strtoupper(Str::random(5));

                $order = Order::create([
                    'order_number' => $orderNumber,
                    'user_id' => $user->id,
                    'coupon_id' => $cart->coupon_id,
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
                    'subtotal' => $totals['subtotal'],
                    'discount_amount' => $totals['discount_amount'],
                    'delivery_fee' => $totals['delivery_fee'],
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
                        'custom_notes' => $line['custom_notes'],
                        'is_perishable' => $line['is_perishable'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'line_total' => $line['line_total'],
                    ]);
                }

                $isPrepaid = $validated['payment_method'] === 'gcash';
                Payment::create([
                    'order_id' => $order->id,
                    'method' => $validated['payment_method'],
                    'reference_number' => $isPrepaid
                        ? (($validated['payment_account_ref'] ?? '') ?: 'SIM-GCASH-'.strtoupper(Str::random(6)))
                        : 'COD-'.strtoupper(Str::random(6)),
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
        } catch (DomainException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $request->session()->put('isla_cart_token', Str::uuid()->toString());
        $order->load(['items', 'payment', 'delivery']);
        $this->mailService->sendOrderConfirmation($order);

        return redirect()
            ->route('account.index')
            ->with('success', 'Order #'.$order->order_number.' confirmed! Check your Email Inbox drawer for your digital receipt.');
    }

    private function deliveryError(string $city, string $province, string $zone, bool $hasPerishables): ?string
    {
        if (trim($city) === '' || trim($province) === '') {
            return 'Enter a city or municipality and province to confirm delivery.';
        }

        if ($zone === 'provincial_parcel') {
            return $hasPerishables ? 'Fresh drinks require Local Express. Remove drinks to ship merch by parcel.' : null;
        }

        $normalize = fn (string $area) => Str::of($area)
            ->ascii()
            ->lower()
            ->replaceMatches('/\([^)]*\)/', '')
            ->replaceMatches('/\bcity\b/i', '')
            ->squish()
            ->replaceMatches('/\s*,\s*/', ',')
            ->toString();
        $areas = array_map($normalize, config('cafeisla.local_delivery_areas', []));

        return in_array($normalize($city.','.$province), $areas, true)
            ? null : 'Local Express is unavailable at this address. Choose parcel for merch or use an address in the Local Express area.';
    }
}
