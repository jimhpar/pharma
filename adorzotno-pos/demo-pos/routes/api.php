<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\CartController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\WishlistController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\PromotionController;
use App\Http\Controllers\Api\RecommendationController;
use App\Http\Controllers\Api\SettingsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['web', 'throttle:100,1'])->group(function () {
    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Product routes
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/trending', [RecommendationController::class, 'trending']);
    Route::get('/products/related/{id}', [RecommendationController::class, 'related'])->whereNumber('id');
    Route::get('/products/{id}', [ProductController::class, 'show'])->whereNumber('id');
    
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/categories/{id}/products', [ProductController::class, 'productsByCategory'])->whereNumber('id');
    
    Route::get('/brands', [ProductController::class, 'brands']);
    Route::get('/brands/{id}/products', [ProductController::class, 'productsByBrand'])->whereNumber('id');

    // Cart routes (sync only)
    Route::post('/cart/sync', [CartController::class, 'sync']);
    Route::post('/cart/apply-coupon', [CartController::class, 'applyCoupon']);

    // Wishlist routes (public)
    Route::get('/wishlist', [WishlistController::class, 'index']);

    // Reviews routes
    Route::get('/products/{id}/reviews', [ReviewController::class, 'productReviews'])->whereNumber('id');

    // Promotions routes
    Route::get('/promotions', [PromotionController::class, 'index']);

    // Recommendations routes
    Route::get('/recommendations', [RecommendationController::class, 'index']);

    // Settings routes
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::get('/shipping-info', [SettingsController::class, 'shippingInfo']);

    // Protected routes (authenticated users only)
    Route::middleware('auth:web')->group(function () {
        // Auth routes
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/update-profile', [AuthController::class, 'updateProfile']);

        // Order routes
        Route::post('/orders/checkout', [OrderController::class, 'checkout']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}/invoice', [OrderController::class, 'invoice'])->whereNumber('id');
        Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel'])->whereNumber('id');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id');

        // Wishlist routes (authenticated)
        Route::post('/wishlist/add', [WishlistController::class, 'add']);
        Route::delete('/wishlist/{sku_id}', [WishlistController::class, 'remove'])->whereNumber('sku_id');

        // Reviews routes (authenticated)
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::put('/reviews/{id}', [ReviewController::class, 'update'])->whereNumber('id');
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->whereNumber('id');
    });
});
