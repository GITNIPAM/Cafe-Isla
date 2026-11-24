<?php

namespace App\Http\Controllers;

use App\Models\CartItem;
use App\Models\ProductVariant;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CartController extends Controller
{
    public function __construct(protected CartService $cartService) {}

    public function index(Request $request): View|JsonResponse
    {
        $cart = $this->cartService->getActiveCart($request, false);
        $zone = (string) $request->query('zone', 'local_express');
        $summary = $this->cartService->summarize($cart, $zone);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'summary' => $summary]);
        }

        return view('storefront.cart', [
            'summary' => $summary,
        ]);
    }

    public function addItem(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'variant_id' => ['required', 'integer', 'exists:product_variants,id'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:25'],
            'custom_notes' => ['nullable', 'string', 'max:160'],
        ]);

        $variant = ProductVariant::with('product.islandSeries')->findOrFail($validated['variant_id']);
        $result = $this->cartService->addVariant(
            $request,
            $variant,
            (int) ($validated['quantity'] ?? 1),
            $validated['custom_notes'] ?? null
        );

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($result, $result['ok'] ? 200 : 422);
        }

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function updateItem(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $cart = $this->cartService->getActiveCart($request, false);
        if (! $cart || $cartItem->cart_id !== $cart->id) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:0', 'max:25'],
        ]);

        $result = $this->cartService->updateItemQuantity($cartItem, (int) $validated['quantity']);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($result, $result['ok'] ? 200 : 422);
        }

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function removeItem(Request $request, CartItem $cartItem): RedirectResponse|JsonResponse
    {
        $cart = $this->cartService->getActiveCart($request, false);
        if (! $cart || $cartItem->cart_id !== $cart->id) {
            abort(403);
        }

        $result = $this->cartService->updateItemQuantity($cartItem, 0);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($result);
        }

        return back()->with('success', $result['message']);
    }

    public function applyCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:40'],
        ]);

        $cart = $this->cartService->getActiveCart($request, true);
        $result = $this->cartService->applyCoupon($cart, $validated['code']);

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json($result, $result['ok'] ? 200 : 422);
        }

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }

    public function removeCoupon(Request $request): RedirectResponse|JsonResponse
    {
        $cart = $this->cartService->getActiveCart($request, false);
        if ($cart) {
            $cart->update(['coupon_id' => null]);
        }

        $summary = $this->cartService->summarize($cart?->fresh(['items.variant.product.islandSeries', 'coupon']));

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json(['ok' => true, 'message' => 'Promo code removed.', 'summary' => $summary]);
        }

        return back()->with('success', 'Promo code removed.');
    }
}
