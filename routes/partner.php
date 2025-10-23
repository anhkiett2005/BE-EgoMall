<?php
use Illuminate\Support\Facades\Route;

// Webhook routes

Route::prefix('webhook')
     ->namespace('App\Http\Controllers\Api\Partner')
     ->group(function() {

    // === VnPay === //
    Route::prefix('vnpay')->group(function () {
         Route::namespace('VnPay\Event\MoneyIn')->group(function() {
            Route::get('event-money-in', 'EventMoneyIn@eventMoneyIn')->name('partner.vnpay.event.money-in');
        });
    });



    // === SePay === //
    Route::prefix('sepay')->group(function() {
        Route::namespace('SePay\Event\MoneyIn')->group(function() {
            Route::post('event-money-in', 'EventMoneyIn@eventMoneyIn')->name('partner.sepay.event.money-in');
        });
    });

    // === PayOs === //
    Route::prefix('payos')->group(function () {
        Route::namespace('PayOs\Event\MoneyIn')->group(function() {
            Route::post('event-money-in', 'EventMoneyIn@eventMoneyIn')->name('partner.payos.event.money-in');
        });
    });
});


