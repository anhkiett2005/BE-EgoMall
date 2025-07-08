<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_addresses', function (Blueprint $table) {
            $table->id();

            // Liên kết với user
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');

            // Địa chỉ hành chính
            $table->string('province_code', 20)->collation('utf8mb4_0900_ai_ci');
            $table->string('district_code', 20)->collation('utf8mb4_0900_ai_ci');
            $table->string('ward_code', 20)->collation('utf8mb4_0900_ai_ci');
            $table->string('address_detail');

            // Người nhận
            $table->string('address_name')->nullable()->comment('Tên điểm giao hàng: Nhà, Shop, Công ty');
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email');
            $table->string('phone');

            // Ghi chú & cờ mặc định
            $table->text('note')->nullable();
            $table->boolean('is_default')->default(false);

            // Thời gian
            $table->timestamps();
            $table->softDeletes();

            // Index để tăng hiệu năng
            $table->index('user_id');
            $table->index('province_code');
            $table->index('district_code');
            $table->index('ward_code');

            // Ràng buộc địa lý
            $table->foreign('province_code')
                ->references('code')
                ->on('provinces');

            $table->foreign('district_code')
                ->references('code')
                ->on('districts');

            $table->foreign('ward_code')
                ->references('code')
                ->on('wards');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_addresses');
    }
};