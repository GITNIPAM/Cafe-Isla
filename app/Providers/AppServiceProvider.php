<?php

namespace App\Providers;

use App\Mail\ResendHttpTransport;
use App\Services\CartService;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Mail;
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
        Mail::extend('resend', function (array $config = []) {
            $apiKey = (string) ($config['key'] ?? config('services.resend.key') ?? '');

            return new ResendHttpTransport($apiKey);
        });

        ResetPassword::createUrlUsing(fn ($user, string $token) => rtrim(config('app.url'), '/').route(
            'password.reset', ['token' => $token, 'email' => $user->getEmailForPasswordReset()], absolute: false
        ));

        View::composer('layouts.app', function ($view) use ($cartService) {
            if (! Schema::hasTable('carts')) {
                return;
            }

            $request = request();
            $cart = $cartService->getActiveCart($request, false);
            $cartSummary = $cartService->summarize($cart);

            $view->with('sharedCartSummary', $cartSummary);
            $view->with('sharedEmailCount', $request->user()?->emailLogs()->count() ?? 0);
        });
    }
}
