<?php
use Illuminate\Support\Facades\Route;

// Webhook routes

Route::prefix('webhook')->group(function() {
    // === SePay === //
    Route::prefix('sepay')->group(function() {
        Route::namespace('App\Http\Controllers\Api\Partner\SePay')->group(function() {
            Route::namespace('Event\MoneyIn')->group(function() {
                Route::post('event-money-in', 'EventMoneyIn@eventMoneyIn')->name('partner.sepay.event.money-in');
            });
        });
    });

    // === PayOs === //
    Route::prefix('payos')->group(function () {
        Route::namespace('App\Http\Controllers\Api\Partner\PayOs')->group(function () {
            Route::namespace('Event\MoneyIn')->group(function() {
                Route::post('event-money-in', 'EventMoneyIn@eventMoneyIn')->name('partner.payos.event.money-in');
            });
        });
    });
});


