<?php

namespace App\Console\Commands;

use App\Classes\Common;
use App\Enums\PromotionStatus;
use App\Jobs\SendPromotionMailJob;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

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
        $now = Carbon::now();

        if (Promotion::where('status', 1)->exists()) {
            // $this->info("Đã có chương trình đang hoạt động. Không thể kích hoạt thêm.");
            Log::channel('promotion')->info('Đã có chương trình đang hoạt động. Không thể kích hoạt thêm.');
            return;
        }

        $pendingPromotions = Promotion::where('status', 0)
            ->where('start_date', '<=', $now)
            ->where('end_date', '>=', $now) // chặn mấy cái đã hết hạn
            ->first();

        if(!is_null($pendingPromotions)) {
            $pendingPromotions->status = PromotionStatus::ACTIVE;
            $pendingPromotions->save();

            // send mail
            Common::sendPromotionEmails($pendingPromotions);
        }

        // $this->info("Không có chương trình khuyến mãi nào được kích hoạt.");
        Log::channel('promotion')->info("Không có chương trình khuyến mãi nào được kích hoạt.");
    }
}
