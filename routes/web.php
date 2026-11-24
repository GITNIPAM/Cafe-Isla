<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminPortalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EmailInboxController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\StorefrontController;
use App\Http\Middleware\ShopVisitMiddleware;
use Illuminate\Support\Facades\Route;

// Public Storefront & Presentation
Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/menu', [StorefrontController::class, 'menu'])->name('menu.index');
Route::get('/product/{slug}', [StorefrontController::class, 'show'])->name('product.show');
Route::get('/uploads/products/{filename}', [StorefrontController::class, 'productImage'])
    ->where('filename', '[A-Za-z0-9][A-Za-z0-9_-]*\.(?:jpe?g|png|webp)')
    ->withoutMiddleware(ShopVisitMiddleware::class);
Route::get('/diagrams', [StorefrontController::class, 'diagrams'])->name('diagrams.index');
Route::view('/contact', 'storefront.contact')->name('contact');
Route::view('/delivery-and-returns', 'storefront.delivery-and-returns')->name('delivery.policy');
Route::view('/privacy', 'storefront.privacy')->name('privacy');
Route::post('/newsletter', [NewsletterController::class, 'store'])->middleware('throttle:6,1')->name('newsletter.store');

// Persistent Shopping Bag
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'addItem'])->name('cart.items.add');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateItem'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeItem'])->name('cart.items.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

// Authentication
Route::get('/auth', [AuthController::class, 'index'])->name('auth.index');
Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
Route::middleware('guest')->group(function () {
    Route::get('/forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'send'])->middleware('throttle:6,1')->name('password.email');
    Route::get('/reset-password/{token}', [PasswordResetController::class, 'edit'])->name('password.reset');
    Route::post('/reset-password', [PasswordResetController::class, 'update'])->middleware('throttle:6,1')->name('password.update');
});

// Authenticated Customer Flows
Route::middleware('auth')->group(function () {
    Route::get('/track', [StorefrontController::class, 'track'])->name('orders.track');
    Route::get('/inbox', [EmailInboxController::class, 'index'])->name('inbox.index');
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout/quote', [CheckoutController::class, 'quote'])->name('checkout.quote');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::post('/account/addresses', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
    Route::delete('/account/addresses/{address}', [AccountController::class, 'deleteAddress'])->name('account.addresses.delete');
    Route::post('/account/orders/{order}/cancel', [AccountController::class, 'cancelOrder'])->name('account.orders.cancel');
    Route::post('/account/orders/{order}/return', [AccountController::class, 'requestReturn'])->name('account.orders.return');
});

// Role-Protected Operational Portal (Admin, Staff, Courier)
Route::prefix('admin')
    ->middleware(['auth', 'role:admin,staff,courier'])
    ->group(function () {
        Route::get('/', [AdminPortalController::class, 'index'])->name('admin.index');

        // Inventory & Categories CRUD (Admin & Staff)
        Route::middleware('role:admin,staff')->group(function () {
            Route::post('/products', [AdminPortalController::class, 'storeProduct'])->name('admin.products.store');
            Route::patch('/products/{product}', [AdminPortalController::class, 'updateProduct'])->name('admin.products.update');
            Route::delete('/products/{product}', [AdminPortalController::class, 'deleteProduct'])->name('admin.products.delete');
            Route::patch('/variants/{variant}', [AdminPortalController::class, 'updateVariant'])->name('admin.variants.update');
            Route::post('/categories', [AdminPortalController::class, 'storeCategory'])->name('admin.categories.store');
            Route::post('/returns/{returnRequest}', [AdminPortalController::class, 'processReturn'])->name('admin.returns.process');
        });

        // Order Fulfillment & Delivery Updates (Admin, Staff, Courier)
        Route::patch('/orders/{order}', [AdminPortalController::class, 'updateOrderStatus'])->name('admin.orders.update');

        // Customer & Team RBAC Management (Admin Only)
        Route::middleware('role:admin')->group(function () {
            Route::post('/team', [AdminPortalController::class, 'storeTeamMember'])->name('admin.team.store');
            Route::patch('/users/{user}/role', [AdminPortalController::class, 'updateUserRole'])->name('admin.users.role');
        });
    });
