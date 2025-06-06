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
        route::controller('BannerController')->group(function() {
            Route::get('/banners', 'index');
            Route::post('/banners/create', 'store');
            Route::get('/banners/{id}', 'show');
            Route::put('/banners/update/{id}', 'update');
            Route::delete('/banners/delete/{id}', 'destroy');
        });
        route::controller('SlidersController')->group(function() {
            Route::get('/sliders', 'index');
            Route::post('/sliders/create', 'store');
            Route::get('/sliders/{id}', 'show');
            Route::put('/sliders/update/{id}', 'update');
            Route::delete('/sliders/delete/{id}', 'destroy');
        });
        route::controller('SliderImagesController')->group(function() {
            Route::get('/slider-images', 'index');
            Route::post('/slider-images/create', 'store');
            Route::get('/slider-images/{id}', 'show');
            Route::put('/slider-images/update/{id}', 'update');
            Route::delete('/slider-images/delete/{id}', 'destroy');
        });
    });


