<?php

namespace App\Console\Commands;

use App\Models\Promotion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class ActivatePromotions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotions:activate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Activate the promotion if it does not overlap with the ongoing program.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $today = Carbon::today();

        $pendingPromotions = Promotion::where('status', 0)
            ->whereDate('start_date', '<=', $today)
            ->orderBy('start_date')
            ->get();

        foreach ($pendingPromotions as $promotion) {
            $start = Carbon::parse($promotion->start_date);
            $end = Carbon::parse($promotion->end_date);

            // Kiểm tra trùng thời gian với chương trình đang hoạt động
            $hasConflict = Promotion::where('status', 1)
                ->where(function ($q) use ($start, $end) {
                    $q->whereBetween('start_date', [$start, $end])
                        ->orWhereBetween('end_date', [$start, $end])
                        ->orWhere(function ($q1) use ($start, $end) {
                            $q1->where('start_date', '<', $start)
                                ->where('end_date', '>', $end);
                        });
                })
                ->exists();

            if (!$hasConflict) {
                $promotion->update(['status' => 1]);
                $this->info("Đã kích hoạt chương trình khuyến mãi: {$promotion->name}");
                return;
            }
        }

        $this->info("Không có chương trình khuyến mãi nào được kích hoạt.");
    }

}
