<?php

use App\Http\Controllers\Api\Auth\AuthController;
use App\Http\Controllers\CategoryController;
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
    });

Route::prefix('v1/auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);

    Route::middleware([JwtCookieAuth::class, Authenticate::class])->group(function () {
        // Lấy thông tin user
        Route::get('user',    [AuthController::class, 'user']);

        // **Thêm route cập nhật profile ở đây**
        Route::post('user',    [AuthController::class, 'update']);

        // Đổi mật khẩu
        Route::post('change-password', [AuthController::class, 'changePassword']);

        // Logout
        Route::post('logout', [AuthController::class, 'logout']);
        
        // Refresh token
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});