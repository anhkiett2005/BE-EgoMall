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
        route::controller('BannerController')->group(function () {
            Route::get('/banners', 'index');
            Route::post('/banners/create', 'store');
            Route::get('/banners/{id}', 'show');
            Route::put('/banners/update/{id}', 'update');
            Route::delete('/banners/delete/{id}', 'destroy');
        });
        route::controller('SlidersController')->group(function () {
            Route::get('/sliders', 'index');
            Route::post('/sliders/create', 'store');
            Route::get('/sliders/{id}', 'show');
            Route::put('/sliders/update/{id}', 'update');
            Route::delete('/sliders/delete/{id}', 'destroy');
        });
        route::controller('SliderImagesController')->group(function () {
            Route::get('/slider-images', 'index');
            Route::post('/slider-images/create', 'store');
            Route::get('/slider-images/{id}', 'show');
            Route::put('/slider-images/update/{id}', 'update');
            Route::delete('/slider-images/delete/{id}', 'destroy');
        });

        // Routes API Product
        Route::controller('ProductController')->group(function () {
            Route::get('/products', 'index');
            Route::get('/product/{slug}', 'show');
        });

        // Routes API Promotion
        Route::controller('PromotionController')->group(function () {
            Route::get('/promotions', 'getPromotionMap');
        });
    });


Route::prefix('v1/admin')
    ->namespace('App\Http\Controllers\Api\Admin')
    ->middleware(['inject.api.auth.header', 'api.auth.check', 'role:admin,super-admin','permission:manage-products'])
    ->group(function () {
        // Routes API Product
        Route::controller('ProductController')->group(function () {
            Route::get('/products', 'index')->name('admin.products.index');
            Route::get('/product/{slug}', 'show')->name('admin.product.show');
            Route::post('/products/create', 'store')->name('admin.products.store');
            Route::put('/products/{slug}','update')->name('admin.products.update');
            Route::delete('/products/{slug}','destroy')->name('admin.products.destroy');
        });

        // Routes API Promotion
        Route::controller('PromotionController')->group(function() {
            Route::get('/promotions','index')->name('admin.promotions.index');
            Route::get('/promotion/{id}', 'show')->name('admin.promotions.show');
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
    Route::get('redirect/google',[AuthController::class, 'redirectToGoogle']);
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
