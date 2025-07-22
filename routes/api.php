<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\JwtCookieAuth;
use Illuminate\Session\Middleware\StartSession;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate;

// Front route
Route::prefix('v1/front')
    ->namespace('App\Http\Controllers\Api\Front')
    ->group(function () {
        // Routes API User Addresses
        Route::middleware(['inject.api.auth.header', 'api.auth.check'])
            ->controller('UserAddressController')
            ->prefix('user/addresses')
            ->group(function () {
                Route::get('/', 'index');
                Route::get('/{id}', 'show');
                Route::post('/', 'store');
                Route::put('/{id}', 'update');
                Route::delete('/{id}', 'destroy');
                Route::patch('/{id}/default', 'setDefault');
                // Route::patch('/{id}/restore', 'restore');
            });

        // Routes API Location
        Route::controller('LocationController')->group(function () {
            Route::get('/location/provinces', 'getProvinces');
            Route::get('/location/provinces/{code}/districts', 'getDistricts');
            Route::get('/location/districts/{code}/wards', 'getWards');
        });

        // Route lấy danh sách phương thức vận chuyển theo tỉnh
        Route::controller('ShippingMethodController')->group(function () {
            Route::get('/shipping-methods', 'index');
            Route::get('/shipping-methods/list', 'list');
        });

        Route::prefix('user/wishlists')
            ->middleware(['inject.api.auth.header', 'api.auth.check'])
            ->controller('WishlistController')
            ->group(function () {
                Route::get('/', 'index');
                Route::post('/', 'store');
                Route::delete('/{productSlug}', 'destroy');
            });

        // Routes API Order History
        Route::prefix('user/orders')
            ->middleware(['inject.api.auth.header', 'api.auth.check'])
            ->controller('OrderHistoryController')
            ->group(function () {
                Route::get('/', 'index'); // GET /v1/front/user/orders?status=...
                Route::get('{unique_id}', 'show');
            });


        // Routes API Category
        Route::controller('CategoryController')->group(function () {
            Route::get('/categories', 'index');
        });
        // Routes API Brand
        Route::controller('BrandController')->group(function () {
            Route::get('/brands', 'index');
        });

        // Routes API Banner
        Route::controller('BannerController')->group(function () {
            Route::get('/banners', 'index');
            // Route::post('/banners/create', 'store');
            // Route::get('/banners/{id}', 'show');
            // Route::put('/banners/update/{id}', 'update');
            // Route::delete('/banners/delete/{id}', 'destroy');
        });

        Route::controller('BlogController')->group(function () {
            Route::get('/blogs', 'index');
            Route::get('/blogs/latest', 'latest');
            Route::get('/blogs/top-viewed', 'topViewed');
            Route::get('/blogs/{slug}', 'showBySlug');
        });

        // Routes API Sliders
        Route::controller('SliderController')->group(function () {
            Route::get('/sliders', 'index');
            // Route::post('/sliders/create', 'store');
            // Route::get('/sliders/{id}', 'show');
            // Route::put('/sliders/update/{id}', 'update');
            // Route::delete('/sliders/delete/{id}', 'destroy');
        });
        // route::controller('SliderImagesController')->group(function () {
        //     Route::get('/slider-images', 'index');
        //     Route::post('/slider-images/create', 'store');
        //     Route::get('/slider-images/{id}', 'show');
        //     Route::put('/slider-images/update/{id}', 'update');
        //     Route::delete('/slider-images/delete/{id}', 'destroy');
        // });

        // Routes API Product
        Route::controller('ProductController')->group(function () {
            Route::get('/products', 'index');
            Route::get('/product/{slug}', 'show');
        });

        // Routes API Promotion
        Route::controller('PromotionController')->group(function () {
            Route::get('/promotions', 'getPromotionMap');
        });

        // Routes API Coupon
        Route::controller('CouponController')->group(function () {
            Route::get('/vouchers', 'index');
        });

        // Routes API Search
        Route::controller('SearchController')->group(function () {
            Route::get('/search', 'index');
        });

        // Routes API Chatbot AI
        Route::controller('AIChatController')->group(function () {
            Route::post('/chat-ai', 'chat');
            Route::get('/chat-ai/history', 'history');
        });

        // Routes API Upload Image to Cloudinary
        Route::controller('UploadController')->group(function () {
            Route::post('/uploads', 'upload')->middleware('check.token.upload');
        });

        // Routes API Orders
        Route::controller('OrderController')
            ->middleware(['inject.api.auth.header', 'api.auth.check'])
            ->group(function () {
                Route::post('/checkout-orders', 'checkOutOrders');
                // Route::get('/cancel-orders/{uniqueId}', 'cancelOrders');
                Route::post('user/cancel-orders/{uniqueId}', 'cancelOrders');
            });

        // Routes API Review
        Route::prefix('user/reviews')
            ->middleware(['inject.api.auth.header', 'api.auth.check'])
            ->controller('ReviewController')
            ->group(function () {
                Route::post('/', 'store');
                Route::post('/{id}', 'update');
                Route::get('/{id}', 'show');
            });

        Route::prefix('products/{slug}/reviews')
            ->controller('ReviewController')
            ->group(function () {
                Route::get('/', 'index');
            });

        // Routes API VnPay
        Route::controller('VnPayController')->group(function () {
            Route::get('/payment/vnpay/callback', 'paymentSuccess');
        });

        // Routes API Momo
        Route::controller('MomoController')->group(function () {
            Route::get('/payment/momo/redirect', 'handleRedirect')->name('payment.momo.redirect');
            Route::post('/payment/momo/ipn', 'handleIpn')->name('payment.momo.ipn');
        });
    });

Route::prefix('v1/admin')
    ->namespace('App\Http\Controllers\Api\Admin')
    ->middleware(['inject.api.auth.header', 'api.auth.check', 'role:admin,super-admin', 'permission:manage-products,manage-categories'])
    ->group(function () {
        // Routes API Shipping Methods
        Route::controller('ShippingMethodController')
            ->prefix('shipping-methods')
            ->group(function () {
                Route::get('/', 'index')->name('admin.shipping-methods.index');
                Route::get('/{id}', 'show')->name('admin.shipping-methods.show');
                Route::post('/', 'store')->name('admin.shipping-methods.store');
                Route::put('/{id}', 'update')->name('admin.shipping-methods.update');
                Route::delete('/{id}', 'destroy')->name('admin.shipping-methods.destroy');
            });


        // Route API Shipping Zones
        Route::controller('ShippingZoneController')
        ->prefix('shipping-methods')
        ->group(function () {
            Route::post('/{shippingMethodId}/zones', 'store')->name('admin.shipping-methods.zones.store');
            Route::put('/{shippingMethodId}/zones/{zoneId}', 'update')->name('admin.shipping-methods.zones.update');
            Route::delete('/{shippingMethodId}/zones/{zoneId}', 'destroy')->name('admin.shipping-methods.zones.destroy');
        });


        // Routes API Variant Options
        Route::controller('VariantOptionController')->group(function () {
            Route::get('/variant-options', 'index')->name('admin.variant-options.index');
            Route::get('/variant-options/{id}', 'show')->name('admin.variant-options.show');
            Route::post('/variant-options', 'store')->name('admin.variant-options.store');
            Route::post('/variant-options/{optionId}/values', 'createValues')->name('admin.variant-options.values.store');
            Route::put('/variant-options/{id}', 'update')->name('admin.variant-options.update');
            Route::delete('/variant-options/{id}', 'destroy')->name('admin.variant-options.destroy');
        });
        // Phản hồi đánh giá
        Route::controller('ReviewReplyController')
            ->group(function () {
                Route::post('/reviews/reply', 'store')->name('admin.reviews.reply');
                Route::put('/reviews/{reviewId}/reply', 'update')->name('admin.reviews.reply.update');
            });

        // Quản lý đánh giá
        Route::controller('ReviewAdminController')
            ->group(function () {
                Route::get('/reviews', 'index');
                Route::put('/reviews/{reviewId}/status', 'updateStatus');
                Route::delete('/reviews/{reviewId}', 'destroy');
            });


        // Routes API User
        Route::controller('UserController')->group(function () {
            Route::get('/users', 'index')->name('admin.users.index');
            Route::get('/user/{id}', 'show')->name('admin.users.show');
            Route::put('/user/{id}', 'update')->name('admin.users.update');
        });

        // Routes API Product
        Route::controller('ProductController')->group(function () {
            Route::get('/products', 'index')->name('admin.products.index');
            Route::get('/product/{slug}', 'show')->name('admin.product.show');
            Route::post('/products/create', 'store')->name('admin.products.store');
            Route::put('/products/{id}', 'update')->name('admin.products.update');
            Route::delete('/products/{slug}', 'destroy')->name('admin.products.destroy');
        });

        // Routes API Category
        Route::controller('CategoryController')->group(function () {
            Route::get('/categories', 'index')->name('admin.categories.index');
            Route::get('/categories/{slug}', 'show')->name('admin.categories.show');
            Route::post('/categories/create', 'store')->name('admin.categories.store');
            Route::put('/categories/{id}', 'update')->name('admin.categories.update');
            Route::delete('/categories/{slug}', 'destroy')->name('admin.categories.destroy');
        });

        // Routes API Brand
        Route::controller('BrandController')->group(function () {
            Route::get('/brands', 'index')->name('admin.brands.index');
            Route::get('/brands/trashed', 'trashed')->name('admin.brands.trashed');
            Route::patch('/brands/restore/{id}', 'restore')->name('admin.brands.restore');
            Route::get('/brands/{id}', 'show')->name('admin.brands.show');
            Route::post('/brands', 'store')->name('admin.brands.store');
            Route::post('/brands/{id}', 'update')->name('admin.brands.update');
            Route::delete('/brands/{id}', 'destroy')->name('admin.brands.destroy');
        });

        // Routes API Promotion
        Route::controller('PromotionController')->group(function () {
            Route::get('/promotions', 'index')->name('admin.promotions.index');
            Route::get('/promotion/{id}', 'show')->name('admin.promotions.show');
            Route::post('/promotions/create', 'store')->name('admin.promotions.store');
            Route::put('/promotions/{id}', 'update')->name('admin.promotions.update');
            Route::delete('/promotions/{id}', 'destroy')->name('admin.promotions.destroy');
        });

        // Routes API Blog
        Route::controller('BlogController')->group(function () {
            Route::get('/blogs', 'index')->name('admin.blogs.index');
            Route::get('/blogs/top-viewed', 'topViewed')->name('admin.blogs.topViewed');
            Route::get('/blogs/{id}', 'show')->name('admin.blogs.show');
            Route::post('/blogs', 'store')->name('admin.blogs.store');
            Route::post('/blogs/{id}', 'update')->name('admin.blogs.update');
            Route::delete('/blogs/{id}', 'destroy')->name('admin.blogs.destroy');
            Route::patch('/blogs/restore/{id}', 'restore')->name('admin.blogs.restore');
        });

        // Routes API Coupon
        Route::controller('CouponController')->group(function () {
            Route::get('/coupons', 'index')->name('admin.coupons.index');
            Route::get('/coupons/{id}', 'show')->name('admin.coupons.show');
            Route::post('/coupons', 'store')->name('admin.coupons.store');
            Route::put('/coupons/{id}', 'update')->name('admin.coupons.update');
            Route::delete('/coupons/{id}', 'destroy')->name('admin.coupons.destroy');
            Route::post('/coupons/{id}/restore', 'restore')->name('admin.coupons.restore');
        });

        // Routes API Cod
        Route::controller('CodController')->group(function () {
            Route::post('/payment/cod/confirm-payment/{order}', 'processConfirmPayment');
        });


        // Routes API Order
        Route::controller('OrderController')->group(function () {
            Route::get('/orders', 'index')->name('admin.orders.index');
            Route::get('/order/{uniqueId}', 'show')->name('admin.orders.show');
            Route::post('/orders/change-status/{uniqueId}', 'update')->name('admin.orders.change-status');
        });

        Route::controller('DashboardController')->group(function () {
            Route::get('/dashboard/statistics', 'index')->name('admin.dashboard.statistics');
        });
    });

Route::prefix('v1/auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('verify-otp', [AuthController::class, 'verifyOtp']);
    Route::post('resend-otp', [AuthController::class, 'resendOtp']);
    Route::post('forgot-password', [AuthController::class, 'sendForgotPasswordOtp']);
    Route::post('verify-reset-otp', [AuthController::class, 'verifyResetOtp']);
    Route::post('set-new-password', [AuthController::class, 'setNewPassword']);
    Route::post('login',    [AuthController::class, 'login']);

    // Login với google
    Route::get('redirect/google', [AuthController::class, 'redirectToGoogle']);
    Route::get('callback/google', [AuthController::class, 'handleGoogleCallback']);

    // Login với facebook
    Route::get('redirect/facebook', [AuthController::class, 'redirectToFacebook']);
    Route::get('callback/facebook', [AuthController::class, 'handleFacebookCallback']);

    Route::middleware(['inject.api.auth.header', 'api.auth.check'])->group(function () {
        Route::get('user',    [AuthController::class, 'user']);
        Route::post('user',    [AuthController::class, 'update']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});
