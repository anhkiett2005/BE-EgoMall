<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Thời điểm user gửi yêu cầu hoàn trả
            $table->timestamp('return_requested_at')->nullable()->after('delivered_at');

            // Lý do hoàn trả (ngắn gọn)
            $table->string('return_reason', 255)->nullable()->after('return_requested_at');

            // Trạng thái xử lý hoàn trả
            $table->enum('return_status', ['requested', 'approved', 'rejected', 'completed'])
                  ->nullable()
                  ->after('return_reason');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['return_requested_at', 'return_reason', 'return_status']);
        });
    }
};