<?php

namespace App\Observers;

use App\Classes\Common;
use App\Models\Promotion;
use Illuminate\Support\Facades\Cache;

class PromotionObserver
{
    /**
     * Handle the Promotion "created" event.
     */
    public function created(Promotion $promotion): void
    {
        $this->clearAndCache();
    }

    /**
     * Handle the Promotion "updated" event.
     */
    public function updated(Promotion $promotion): void
    {
        $this->clearAndCache();
    }

    /**
     * Handle the Promotion "deleted" event.
     */
    public function deleted(Promotion $promotion): void
    {
        $this->clearAndCache();
    }

    /**
     * Handle the Promotion "restored" event.
     */
    public function restored(Promotion $promotion): void
    {
        $this->clearAndCache();
    }

    /**
     * Handle the Promotion "force deleted" event.
     */
    public function forceDeleted(Promotion $promotion): void
    {
        $this->clearAndCache();
    }

    private function clearAndCache()
    {
        // Xoa cache
        Cache::forget('active_promotions');

        // Cache promotion
        Common::getActivePromotion();
    }
}
