<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use App\Models\Promotion;
use App\Observers\PromotionObserver;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [];

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        parent::boot();

        // Đăng ký observer
        Promotion::observe(PromotionObserver::class);
    }
}
