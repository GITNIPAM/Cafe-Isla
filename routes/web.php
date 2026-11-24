<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\AdminPortalController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\CartController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EmailInboxController;
use App\Http\Controllers\StorefrontController;
use Illuminate\Support\Facades\Route;

// Public Storefront & Presentation
Route::get('/', [StorefrontController::class, 'home'])->name('home');
Route::get('/menu', [StorefrontController::class, 'menu'])->name('menu.index');
Route::get('/product/{slug}', [StorefrontController::class, 'show'])->name('product.show');
Route::get('/track', [StorefrontController::class, 'track'])->name('orders.track');
Route::get('/diagrams', [StorefrontController::class, 'diagrams'])->name('diagrams.index');

// Persistent Shopping Bag
Route::get('/cart', [CartController::class, 'index'])->name('cart.index');
Route::post('/cart/items', [CartController::class, 'addItem'])->name('cart.items.add');
Route::patch('/cart/items/{cartItem}', [CartController::class, 'updateItem'])->name('cart.items.update');
Route::delete('/cart/items/{cartItem}', [CartController::class, 'removeItem'])->name('cart.items.remove');
Route::post('/cart/coupon', [CartController::class, 'applyCoupon'])->name('cart.coupon.apply');
Route::delete('/cart/coupon', [CartController::class, 'removeCoupon'])->name('cart.coupon.remove');

// Live Dual-Channel Email Inbox Feed
Route::get('/inbox', [EmailInboxController::class, 'index'])->name('inbox.index');

// Authentication
Route::get('/auth', [AuthController::class, 'index'])->name('auth.index');
Route::post('/auth/register', [AuthController::class, 'register'])->name('auth.register');
Route::post('/auth/login', [AuthController::class, 'login'])->name('auth.login');
Route::post('/auth/logout', [AuthController::class, 'logout'])->name('auth.logout');

// Authenticated Customer Flows
Route::middleware('auth')->group(function () {
    Route::get('/checkout', [CheckoutController::class, 'index'])->name('checkout.index');
    Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

    Route::get('/account', [AccountController::class, 'index'])->name('account.index');
    Route::patch('/account/profile', [AccountController::class, 'updateProfile'])->name('account.profile.update');
    Route::post('/account/addresses', [AccountController::class, 'storeAddress'])->name('account.addresses.store');
    Route::delete('/account/addresses/{address}', [AccountController::class, 'deleteAddress'])->name('account.addresses.delete');
    Route::post('/account/orders/{order}/cancel', [AccountController::class, 'cancelOrder'])->name('account.orders.cancel');
    Route::post('/account/orders/{order}/return', [AccountController::class, 'requestReturn'])->name('account.orders.return');
});

// Role-Protected Operational Portal (Admin & Courier)
Route::prefix('admin')
    ->middleware(['auth', 'role:admin,courier'])
    ->group(function () {
        Route::get('/', [AdminPortalController::class, 'index'])->name('admin.index');

        // Inventory & Categories CRUD (Admin Only)
        Route::middleware('role:admin')->group(function () {
            Route::post('/products', [AdminPortalController::class, 'storeProduct'])->name('admin.products.store');
            Route::patch('/products/{product}', [AdminPortalController::class, 'updateProduct'])->name('admin.products.update');
            Route::delete('/products/{product}', [AdminPortalController::class, 'deleteProduct'])->name('admin.products.delete');
            Route::post('/products/{product}/variants', [AdminPortalController::class, 'storeVariant'])->name('admin.variants.store');
            Route::patch('/variants/{variant}', [AdminPortalController::class, 'updateVariant'])->name('admin.variants.update');
            Route::post('/categories', [AdminPortalController::class, 'storeCategory'])->name('admin.categories.store');
            Route::patch('/categories/{category}', [AdminPortalController::class, 'updateCategory'])->name('admin.categories.update');
            Route::post('/returns/{returnRequest}', [AdminPortalController::class, 'processReturn'])->name('admin.returns.process');
        });

        // Order Fulfillment & Delivery Updates (Admin & Courier)
        Route::patch('/orders/{order}', [AdminPortalController::class, 'updateOrderStatus'])->name('admin.orders.update');

        // Customer & Team RBAC Management (Admin Only)
        Route::middleware('role:admin')->group(function () {
            Route::post('/team', [AdminPortalController::class, 'storeTeamMember'])->name('admin.team.store');
            Route::patch('/users/{user}/role', [AdminPortalController::class, 'updateUserRole'])->name('admin.users.role');
        });
    });
