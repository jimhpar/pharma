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
use App\Http\Controllers\Api\BannerController;
use App\Http\Controllers\Api\FaqController;
use App\Http\Controllers\Api\ShipmentZoneController;

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

Route::middleware(['throttle:100,1'])->group(function () {
    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register']);
    Route::post('/auth/login', [AuthController::class, 'login']);

    // Product routes
    Route::get('/products', [ProductController::class, 'index']);
    Route::get('/products/search', [ProductController::class, 'search']);
    Route::get('/products/featured', [ProductController::class, 'featuredProducts']);
    Route::get('/products/flash-deals', [ProductController::class, 'flashDealsProducts']);
    Route::get('/products/product-type/{product_type}', [ProductController::class, 'productsByProductType']);
    Route::get('/products/generic-name/{generic_name}', [ProductController::class, 'productsByGenericName']);
    Route::get('/products/trending', [RecommendationController::class, 'trending']);
    Route::get('/products/related/{slug}', [RecommendationController::class, 'related']);
    Route::get('/products/{slug}', [ProductController::class, 'show']);
    
    Route::get('/categories', [ProductController::class, 'categories']);
    Route::get('/categories/{slug}/products', [ProductController::class, 'productsByCategory']);
    
    Route::get('/brands', [ProductController::class, 'brands']);
    Route::get('/brands/{slug}/products', [ProductController::class, 'productsByBrand']);

    // Banner routes
    Route::get('/banners', [BannerController::class, 'index']);
    Route::get('/banners/{id}', [BannerController::class, 'show'])->whereNumber('id');

    // FAQ routes
    Route::get('/faqs', [FaqController::class, 'index']);
    Route::get('/faqs/{id}', [FaqController::class, 'show'])->whereNumber('id');

    // Cart routes (sync only)
    Route::post('/cart/sync', [CartController::class, 'sync']);
    Route::post('/cart/apply-coupon', [CartController::class, 'applyCoupon']);

    // Reviews routes
    Route::get('/products/{slug}/reviews', [ReviewController::class, 'productReviews']);

    // Promotions routes
    Route::get('/promotions', [PromotionController::class, 'index']);

    // Recommendations routes
    Route::get('/recommendations', [RecommendationController::class, 'index']);

    // Settings routes
    Route::get('/settings', [SettingsController::class, 'index']);
    Route::get('/shipping-info', [SettingsController::class, 'shippingInfo']);
    Route::get('/shipment-zones', [ShipmentZoneController::class, 'index']);

    // Protected routes (authenticated users only)
    Route::middleware('auth:sanctum')->group(function () {
        // Auth routes
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/update-profile', [AuthController::class, 'updateProfile']);
        Route::post('/auth/change-password', [AuthController::class, 'changePassword']);

        // Order routes
        Route::post('/orders/checkout', [OrderController::class, 'checkout']);
        Route::get('/orders', [OrderController::class, 'index']);
        Route::get('/orders/{id}/invoice', [OrderController::class, 'invoice'])->whereNumber('id');
        Route::put('/orders/{id}/cancel', [OrderController::class, 'cancel'])->whereNumber('id');
        Route::get('/orders/{id}', [OrderController::class, 'show'])->whereNumber('id');

        // Wishlist routes (authenticated)
        Route::get('/wishlist', [WishlistController::class, 'index']);
        Route::post('/wishlist/add', [WishlistController::class, 'add']);
        Route::delete('/wishlist/{sku_id}', [WishlistController::class, 'remove'])->whereNumber('sku_id');

        // Reviews routes (authenticated)
        Route::post('/reviews', [ReviewController::class, 'store']);
        Route::put('/reviews/{id}', [ReviewController::class, 'update'])->whereNumber('id');
        Route::delete('/reviews/{id}', [ReviewController::class, 'destroy'])->whereNumber('id');
    });
});
