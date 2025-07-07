<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->unique();
            $table->longText('content'); // Nội dung HTML từ CKEditor
            $table->string('excerpt')->nullable(); // Mô tả ngắn
            $table->string('image_url')->nullable();

            $table->enum('status', [
                'draft',        // Bản nháp, chưa đăng
                'scheduled',    // Đã lên lịch, chờ đến ngày
                'published',    // Đã đăng
                'archived'      // Lưu trữ, không hiển thị
            ])->default('draft');

            $table->unsignedBigInteger('views')->default(0); // Lượt xem

            $table->unsignedBigInteger('category_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamp('published_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('categories')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
