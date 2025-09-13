<?php

namespace App\Console\Commands;

use App\Models\Promotion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ExpirePromotions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotions:expire';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Disable expired promotions at midnight';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now()->startOfDay();

        // $expiredPromotions = Promotion::where('status', true)
        //     ->where('end_date', '<', $now)
        //     ->get();

        // $count = $expiredPromotions->count();

        // foreach ($expiredPromotions as $promotion) {
        //     $promotion->update(['status' => false]);
        // }

        $count = Promotion::where('status', true)
                          ->where('end_date', '<=', $now)
                          ->update(['status' => false]);

        // $this->info("Đã cập nhật $count chương trình khuyến mãi hết hạn.");
        Log::channel('promotion')->info("Đã cập nhật $count chương trình khuyến mãi hết hạn.");
    }
}
