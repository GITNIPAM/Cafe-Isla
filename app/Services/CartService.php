<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Coupon;
use App\Models\ProductVariant;
use App\Models\ShopVisit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class CartService
{
    public const FREE_DELIVERY_THRESHOLD = 1200.00;

    public const LOCAL_EXPRESS_FEE = 65.00;

    public const PROVINCIAL_PARCEL_FEE = 120.00;

    public function resolveToken(Request $request): string
    {
        $token = $request->session()->get('isla_cart_token')
            ?: $request->cookie('isla_cart_token');

        if (! $token) {
            $token = Str::uuid()->toString();
        }

        $request->session()->put('isla_cart_token', $token);

        return $token;
    }

    public function getActiveCart(Request $request, bool $createIfMissing = true): ?Cart
    {
        $token = $this->resolveToken($request);
        $userId = Auth::id();

        $cart = Cart::with(['items.variant.product.islandSeries', 'coupon'])
            ->where('status', 'active')
            ->where(function ($q) use ($token, $userId) {
                $q->where('session_token', $token);
                if ($userId) {
                    $q->orWhere('user_id', $userId);
                }
            })
            ->latest('updated_at')
            ->first();

        if (! $cart && $createIfMissing) {
            $cart = Cart::create([
                'user_id' => $userId,
                'session_token' => $token,
                'status' => 'active',
            ]);
            $cart->load(['items.variant.product.islandSeries', 'coupon']);
        } elseif ($cart && $userId && ! $cart->user_id) {
            $cart->update(['user_id' => $userId]);
        }

        return $cart;
    }

    public function mergeGuestCartIntoUser(Request $request, User $user): void
    {
        $token = $this->resolveToken($request);

        $guestCart = Cart::with('items')
            ->where('session_token', $token)
            ->where('status', 'active')
            ->first();

        $userCart = Cart::with('items')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->where('session_token', '!=', $token)
            ->latest('updated_at')
            ->first();

        if ($guestCart && $userCart) {
            foreach ($guestCart->items as $guestItem) {
                $existing = $userCart->items()
                    ->where('product_variant_id', $guestItem->product_variant_id)
                    ->first();
                if ($existing) {
                    $existing->update([
                        'quantity' => min(
                            $existing->quantity + $guestItem->quantity,
                            max(1, (int) $existing->variant->stock_qty)
                        ),
                    ]);
                } else {
                    $guestItem->update(['cart_id' => $userCart->id]);
                }
            }
            if (! $userCart->coupon_id && $guestCart->coupon_id) {
                $userCart->update(['coupon_id' => $guestCart->coupon_id]);
            }
            $guestCart->delete();
            $userCart->update(['session_token' => $token]);
        } elseif ($guestCart) {
            $guestCart->update(['user_id' => $user->id]);
        } elseif ($userCart) {
            $userCart->update(['session_token' => $token]);
        }
    }

    public function addVariant(Request $request, ProductVariant $variant, int $quantity = 1, ?string $notes = null): array
    {
        if (! $variant->is_active || $variant->product->is_archived) {
            return ['ok' => false, 'message' => 'This selection is currently archived.'];
        }

        if ($variant->stock_qty < 1) {
            return ['ok' => false, 'message' => $variant->product->name.' ('.$variant->label.') is sold out for this batch.'];
        }

        $cart = $this->getActiveCart($request, true);
        $existing = $cart->items()->where('product_variant_id', $variant->id)->first();
        $targetQty = ($existing ? $existing->quantity : 0) + max(1, $quantity);

        if ($targetQty > $variant->stock_qty) {
            return [
                'ok' => false,
                'message' => 'Only '.$variant->stock_qty.' left in this batch for '.$variant->product->name.' ('.$variant->label.').',
            ];
        }

        if ($existing) {
            $existing->update([
                'quantity' => $targetQty,
                'unit_price' => $variant->price,
                'custom_notes' => $notes ?: $existing->custom_notes,
            ]);
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_variant_id' => $variant->id,
                'quantity' => max(1, $quantity),
                'custom_notes' => $notes,
                'unit_price' => $variant->price,
            ]);
        }

        ShopVisit::where('session_token', $cart->session_token)
            ->whereNull('converted_order_id')
            ->update(['had_cart' => true]);

        return [
            'ok' => true,
            'message' => $variant->product->name.' ('.$variant->label.') poured into your bag.',
            'summary' => $this->summarize($cart->fresh(['items.variant.product.islandSeries', 'coupon'])),
        ];
    }

    public function updateItemQuantity(CartItem $item, int $quantity): array
    {
        if ($quantity <= 0) {
            $cart = $item->cart;
            $item->delete();

            return [
                'ok' => true,
                'message' => 'Item removed from your bag.',
                'summary' => $this->summarize($cart->fresh(['items.variant.product.islandSeries', 'coupon'])),
            ];
        }

        $variant = $item->variant;
        if ($quantity > $variant->stock_qty) {
            return [
                'ok' => false,
                'message' => 'Only '.$variant->stock_qty.' available in stock for '.$variant->product->name.'.',
            ];
        }

        $item->update([
            'quantity' => $quantity,
            'unit_price' => $variant->price,
        ]);

        return [
            'ok' => true,
            'message' => 'Bag quantity updated.',
            'summary' => $this->summarize($item->cart->fresh(['items.variant.product.islandSeries', 'coupon'])),
        ];
    }

    public function applyCoupon(Cart $cart, string $code): array
    {
        $coupon = Coupon::whereRaw('UPPER(code) = ?', [strtoupper(trim($code))])->first();
        if (! $coupon || ! $coupon->is_active || ($coupon->expires_at && $coupon->expires_at->isPast())) {
            return ['ok' => false, 'message' => 'Promo code not recognized or expired.'];
        }

        $subtotal = $this->calculateSubtotal($cart);
        if ($subtotal < (float) $coupon->min_subtotal) {
            return [
                'ok' => false,
                'message' => 'Code '.$coupon->code.' requires a minimum subtotal of ₱'.number_format((float) $coupon->min_subtotal, 0).'.',
            ];
        }

        $cart->update(['coupon_id' => $coupon->id]);

        return [
            'ok' => true,
            'message' => 'Promo '.$coupon->code.' applied.',
            'summary' => $this->summarize($cart->fresh(['items.variant.product.islandSeries', 'coupon'])),
        ];
    }

    public function calculateSubtotal(?Cart $cart): float
    {
        if (! $cart) {
            return 0.0;
        }

        return round((float) $cart->items->sum(fn (CartItem $i) => (float) $i->unit_price * $i->quantity), 2);
    }

    public function summarize(?Cart $cart, string $zone = 'local_express'): array
    {
        if (! $cart || $cart->items->isEmpty()) {
            return [
                'count' => 0,
                'items' => [],
                'subtotal' => 0.0,
                'discount_amount' => 0.0,
                'delivery_fee' => 0.0,
                'grand_total' => 0.0,
                'has_perishables' => false,
                'free_shipping_gap' => self::FREE_DELIVERY_THRESHOLD,
                'coupon_code' => null,
                'delivery_zone' => $zone,
            ];
        }

        $hasPerishables = $cart->items->contains(fn (CartItem $i) => (bool) ($i->variant?->product?->is_perishable));
        $effectiveZone = $hasPerishables ? 'local_express' : $zone;

        $subtotal = $this->calculateSubtotal($cart);
        $discount = $cart->coupon ? $cart->coupon->computeDiscount($subtotal) : 0.0;
        $afterDiscount = max(0.0, $subtotal - $discount);

        $deliveryFee = 0.0;
        if ($afterDiscount < self::FREE_DELIVERY_THRESHOLD) {
            $deliveryFee = $effectiveZone === 'provincial_parcel'
                ? self::PROVINCIAL_PARCEL_FEE
                : self::LOCAL_EXPRESS_FEE;
        }

        if ($cart->coupon && strtoupper($cart->coupon->code) === 'FREESIP' && $effectiveZone === 'local_express') {
            $deliveryFee = 0.0;
            $discount = 0.0;
            $afterDiscount = $subtotal;
        }

        $grandTotal = round($afterDiscount + $deliveryFee, 2);
        $count = (int) $cart->items->sum('quantity');

        $mappedItems = $cart->items->map(function (CartItem $item) {
            $product = $item->variant->product;

            return [
                'id' => $item->id,
                'variant_id' => $item->product_variant_id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'island_name' => $product->islandSeries->name,
                'variant_label' => $item->variant->label,
                'is_perishable' => (bool) $product->is_perishable,
                'quantity' => $item->quantity,
                'max_stock' => $item->variant->stock_qty,
                'unit_price' => (float) $item->unit_price,
                'line_total' => $item->lineTotal(),
                'custom_notes' => $item->custom_notes,
                'image_url' => $product->imageUrl(),
            ];
        })->values()->all();

        return [
            'count' => $count,
            'items' => $mappedItems,
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'delivery_fee' => $deliveryFee,
            'grand_total' => $grandTotal,
            'has_perishables' => $hasPerishables,
            'free_shipping_gap' => max(0.0, round(self::FREE_DELIVERY_THRESHOLD - $afterDiscount, 2)),
            'coupon_code' => $cart->coupon?->code,
            'delivery_zone' => $effectiveZone,
        ];
    }
}
