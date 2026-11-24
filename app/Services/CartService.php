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
use Illuminate\Support\Facades\DB;
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

        DB::transaction(function () use ($token, $user) {
            $this->mergeCarts($token, $user->id);
        }, 3);
    }

    private function mergeCarts(string $token, int $userId): void
    {
        $guestCart = Cart::with('items.variant')
            ->where('session_token', $token)
            ->where('status', 'active')
            ->lockForUpdate()
            ->first();

        $userCart = Cart::where('user_id', $userId)
            ->where('status', 'active')
            ->where('session_token', '!=', $token)
            ->latest('updated_at')
            ->lockForUpdate()
            ->first();

        if ($guestCart && $userCart) {
            foreach ($guestCart->items as $guestItem) {
                $variantItems = $userCart->items()->where('product_variant_id', $guestItem->product_variant_id)->get();
                $remaining = max(0, $guestItem->variant->stock_qty - $variantItems->sum('quantity'));
                $quantity = min($guestItem->quantity, $remaining);
                if ($quantity === 0) {
                    continue;
                }

                $existing = $variantItems->first(fn (CartItem $item) => $item->custom_notes === $guestItem->custom_notes);
                if ($existing) {
                    $existing->increment('quantity', $quantity);
                } else {
                    $guestItem->update(['cart_id' => $userCart->id, 'quantity' => $quantity]);
                }
            }
            if (! $userCart->coupon_id && $guestCart->coupon_id) {
                $userCart->update(['coupon_id' => $guestCart->coupon_id]);
            }
            $guestCart->delete();
            $userCart->update(['session_token' => $token]);
        } elseif ($guestCart) {
            $guestCart->update(['user_id' => $userId]);
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
        $quantity = max(1, $quantity);

        return DB::transaction(function () use ($cart, $quantity, $variant, $notes) {
            $cart = Cart::lockForUpdate()->findOrFail($cart->id);
            if ($cart->status !== 'active') {
                return ['ok' => false, 'message' => 'Your bag changed. Refresh and try again.'];
            }

            $variantItems = $cart->items()->where('product_variant_id', $variant->id)->get();
            if ($variantItems->sum('quantity') + $quantity > $variant->stock_qty) {
                return [
                    'ok' => false,
                    'message' => 'Only '.$variant->stock_qty.' left in this batch for '.$variant->product->name.' ('.$variant->label.').',
                ];
            }

            $existing = $variantItems->first(fn (CartItem $item) => $item->custom_notes === $notes);
            if ($existing) {
                $existing->update([
                    'quantity' => $existing->quantity + $quantity,
                    'unit_price' => $variant->price,
                ]);
            } else {
                $cart->items()->create([
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
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
                'summary' => $this->summarize($cart->load(['items.variant.product.islandSeries', 'coupon'])),
            ];
        }, 3);
    }

    public function updateItem(CartItem $item, int $quantity, array $notes = []): array
    {
        return DB::transaction(function () use ($item, $quantity, $notes) {
            $cart = Cart::lockForUpdate()->find($item->cart_id);
            $item = $cart?->items()->find($item->id);
            if ($cart?->status !== 'active' || ! $item) {
                return ['ok' => false, 'message' => 'Your bag changed. Refresh and try again.'];
            }

            $message = 'Item removed from your bag.';
            if ($quantity <= 0) {
                $item->delete();
            } else {
                $variant = $item->variant;
                $otherQuantity = $cart->items()
                    ->where('product_variant_id', $variant->id)
                    ->whereKeyNot($item->id)
                    ->sum('quantity');
                if ($otherQuantity + $quantity > $variant->stock_qty) {
                    return [
                        'ok' => false,
                        'message' => 'Only '.$variant->stock_qty.' available in stock for '.$variant->product->name.'.',
                    ];
                }

                $item->update([...$notes, 'quantity' => $quantity, 'unit_price' => $variant->price]);
                $message = 'Bag item updated.';
            }

            return [
                'ok' => true,
                'message' => $message,
                'summary' => $this->summarize($cart->load(['items.variant.product.islandSeries', 'coupon'])),
            ];
        }, 3);
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

    public function summarize(?Cart $cart, string $zone = 'local_express', bool $currentPrices = false): array
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

        $count = (int) $cart->items->sum('quantity');
        $quantitiesByVariant = $cart->items->groupBy('product_variant_id')->map->sum('quantity');

        $mappedItems = $cart->items->map(function (CartItem $item) use ($currentPrices, $quantitiesByVariant) {
            $product = $item->variant->product;
            $price = (float) ($currentPrices ? $item->variant->price : $item->unit_price);
            $otherQuantity = $quantitiesByVariant[$item->product_variant_id] - $item->quantity;

            return [
                'id' => $item->id,
                'variant_id' => $item->product_variant_id,
                'product_name' => $product->name,
                'product_slug' => $product->slug,
                'island_name' => $product->islandSeries->name,
                'variant_label' => $item->variant->label,
                'is_perishable' => (bool) $product->is_perishable,
                'quantity' => $item->quantity,
                'max_stock' => max(0, $item->variant->stock_qty - $otherQuantity),
                'unit_price' => $price,
                'line_total' => round($price * $item->quantity, 2),
                'custom_notes' => $item->custom_notes,
                'image_url' => $product->imageUrl(),
            ];
        })->values()->all();

        $subtotal = round(array_sum(array_column($mappedItems, 'line_total')), 2);

        return [
            'count' => $count,
            'items' => $mappedItems,
            ...$this->calculateTotals($subtotal, $cart->coupon, $effectiveZone),
            'has_perishables' => $hasPerishables,
            'coupon_code' => $cart->coupon?->code,
            'delivery_zone' => $effectiveZone,
        ];
    }

    public function calculateTotals(float $subtotal, ?Coupon $coupon, string $zone): array
    {
        $isDeliveryPromo = strtoupper($coupon?->code ?? '') === 'FREESIP';
        $couponValue = $coupon?->computeDiscount($subtotal) ?? 0.0;
        $discount = $isDeliveryPromo ? 0.0 : $couponValue;
        $afterDiscount = max(0.0, $subtotal - $discount);
        $freeDelivery = $afterDiscount >= self::FREE_DELIVERY_THRESHOLD
            || ($isDeliveryPromo && $couponValue > 0 && $zone === 'local_express');
        $standardFee = $zone === 'provincial_parcel' ? self::PROVINCIAL_PARCEL_FEE : self::LOCAL_EXPRESS_FEE;
        $fee = $freeDelivery ? 0.0 : $standardFee;

        return [
            'subtotal' => $subtotal,
            'discount_amount' => $discount,
            'delivery_fee' => $fee,
            'grand_total' => round($afterDiscount + $fee, 2),
            'free_shipping_gap' => $freeDelivery ? 0.0 : round(self::FREE_DELIVERY_THRESHOLD - $afterDiscount, 2),
        ];
    }
}
