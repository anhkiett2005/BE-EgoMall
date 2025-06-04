<?php

use App\Http\Controllers\CategoryController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Front route
Route::prefix('v1/front')
    ->namespace('App\Http\Controllers\Api\Front')
    ->group(function() {
        // Routes API Category
        Route::controller('CategoryController')->group(function() {
            Route::get('/categories', 'index');
        });
        // Routes API Brand
        Route::controller('BrandController')->group(function() {
            Route::get('/brands','index');
        });
    });


