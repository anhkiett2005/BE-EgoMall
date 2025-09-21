<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use PayOS\PayOS;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Đăng ký config cho PayOS
        $this->app->singleton(PayOS::class, function ($app) {
            // Lấy config thông qua $app
            $config = $app['config']['services.payos'];

            return new PayOS(
                $config['client_id'],
                $config['api_key'],
                $config['check_sum_key']
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
