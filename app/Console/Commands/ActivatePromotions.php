<?php

namespace App\Console\Commands;

use App\Classes\Common;
use App\Jobs\SendPromotionMailJob;
use App\Models\Promotion;
use App\Models\User;
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

        if (Promotion::where('status', 1)->exists()) {
            $this->info("Đã có chương trình đang hoạt động. Không thể kích hoạt thêm.");
            return;
        }

        $pendingPromotions = Promotion::where('status', 0)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today) // chặn mấy cái đã hết hạn
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

                try {
                    Common::sendPromotionEmails($promotion);
                } catch (\Throwable $e) {
                    logger()->error('Gửi mail thất bại khi kích hoạt promotion', [
                        'promotion_id' => $promotion->id,
                        'error_message' => $e->getMessage(),
                        'stack_trace' => $e->getTraceAsString(),
                    ]);

                    $this->error("Lỗi khi gửi mail: " . $e->getMessage());
                }


                $this->info("Đã kích hoạt và gửi mail chương trình khuyến mãi: {$promotion->name}");
                return;
            }
        }

        $this->info("Không có chương trình khuyến mãi nào được kích hoạt.");
    }
}
