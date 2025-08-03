<?php

namespace App\Console\Commands;

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

                $totalRecipients = User::where('role_id', 4)
                    ->where('is_active', true)
                    ->whereNotNull('email_verified_at')
                    ->count();

                if ($totalRecipients === 0) {
                    $promotion->update(['is_mail_sent' => true]);
                    $this->info("Không có khách hàng nào nhận được mail.");
                } else {
                    $firstBatch = true;

                    User::where('role_id', 4)
                        ->where('is_active', true)
                        ->whereNotNull('email_verified_at')
                        ->chunk(100, function ($customers) use ($promotion, &$firstBatch) {
                            $count = $customers->count();
                            echo "⏳ Đang gửi mail cho {$count} khách hàng...\n";

                            foreach ($customers as $customer) {
                                SendPromotionMailJob::dispatch($customer, $promotion);
                            }

                            if ($firstBatch) {
                                Promotion::where('id', $promotion->id)->update(['is_mail_sent' => true]);
                                $firstBatch = false;
                            }
                        });
                }

                $this->info("Đã kích hoạt và gửi mail chương trình khuyến mãi: {$promotion->name}");
                return;
            }
        }

        $this->info("Không có chương trình khuyến mãi nào được kích hoạt.");
    }
}
