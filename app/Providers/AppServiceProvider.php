<?php

namespace App\Providers;

use App\Models\EmailLog;
use App\Services\CartService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(CartService $cartService): void
    {
        View::composer('*', function ($view) use ($cartService) {
            if (app()->runningInConsole() && ! app()->runningUnitTests()) {
                return;
            }

            if (! Schema::hasTable('carts')) {
                return;
            }

            $request = request();
            $cart = $cartService->getActiveCart($request, false);
            $cartSummary = $cartService->summarize($cart);

            $inboxQuery = EmailLog::latest('sent_at')->limit(10);
            if (Auth::check() && Auth::user()->role === 'customer') {
                $inboxQuery->where('user_id', Auth::id());
            }

            $view->with('sharedCartSummary', $cartSummary);
            $view->with('sharedEmailLogs', $inboxQuery->get());
        });
    }
}
