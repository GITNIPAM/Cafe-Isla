<?php

namespace App\Http\Middleware;

use App\Models\Cart;
use App\Models\ShopVisit;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class ShopVisitMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || $request->expectsJson()) {
            return $next($request);
        }

        $routeName = $request->route()?->getName() ?? $request->path();
        if (str_starts_with((string) $routeName, 'admin') || str_starts_with((string) $routeName, 'inbox')) {
            return $next($request);
        }

        $token = $request->session()->get('isla_cart_token');
        if (! $token) {
            $token = $request->cookie('isla_cart_token') ?: Str::uuid()->toString();
            $request->session()->put('isla_cart_token', $token);
        }

        $existingVisit = ShopVisit::where('session_token', $token)
            ->where('visited_at', '>=', now()->subMinutes(15))
            ->whereNull('converted_order_id')
            ->first();

        $hasCart = Cart::where('session_token', $token)
            ->where('status', 'active')
            ->whereHas('items')
            ->exists();

        if (! $existingVisit) {
            ShopVisit::create([
                'session_token' => $token,
                'user_id' => $request->user()?->id,
                'route_name' => (string) $routeName,
                'had_cart' => $hasCart,
                'visited_at' => now(),
            ]);
        } else {
            $existingVisit->update([
                'user_id' => $existingVisit->user_id ?? $request->user()?->id,
                'had_cart' => $existingVisit->had_cart || $hasCart,
                'route_name' => (string) $routeName,
            ]);
        }

        return $next($request);
    }
}
