<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\CategoryController;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Middleware\JwtCookieAuth;
use PHPOpenSourceSaver\JWTAuth\Http\Middleware\Authenticate;

// Front route
Route::prefix('v1/front')
    ->namespace('App\Http\Controllers\Api\Front')
    ->group(function () {
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

        // Routes API Upload Image to Cloudinary
        Route::controller('UploadController')->group(function () {
            Route::post('/uploads', 'upload')->middleware('check.token.upload');
        });
    });

Route::prefix('v1/admin')
    ->namespace('App\Http\Controllers\Api\Admin')
    ->middleware(['inject.api.auth.header', 'api.auth.check', 'role:admin,super-admin', 'permission:manage-products,manage-categories'])
    ->group(function () {
        // Routes API Product
        Route::controller('ProductController')->group(function () {
            Route::get('/products', 'index')->name('admin.products.index');
            Route::get('/product/{slug}', 'show')->name('admin.product.show');
            Route::post('/products/create', 'store')->name('admin.products.store');
            Route::put('/products/{slug}', 'update')->name('admin.products.update');
            Route::delete('/products/{slug}', 'destroy')->name('admin.products.destroy');
        });

        // Routes API Category
        Route::controller('CategoryController')->group(function () {
            Route::get('/categories', 'index')->name('admin.categories.index');
            Route::post('/categories/create', 'store')->name('admin.categories.store');
            Route::put('/categories/{slug}', 'update')->name('admin.categories.update');
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
