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
            $table->string('image_url')->nullable(); // Ảnh đại diện (Cloudinary)

            $table->enum('status', ['draft', 'published', 'archived'])->default('draft'); // Trạng thái
            $table->unsignedBigInteger('views')->default(0); // Lượt xem

            $table->foreignId('category_id')->constrained('categories')->nullOnDelete(); // Danh mục
            $table->foreignId('created_by')->constrained('users')->nullOnDelete(); // Người tạo

            $table->boolean('is_published')->default(false); // Hiển thị ra frontend không
            $table->timestamp('published_at')->nullable();   // Ngày bài đăng chính thức

            $table->timestamps(); // created_at & updated_at
            $table->softDeletes(); // deleted_at
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};