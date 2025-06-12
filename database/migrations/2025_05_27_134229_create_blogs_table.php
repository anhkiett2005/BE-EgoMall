<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('blogs', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->string('title'); // Tiêu đề
            $table->string('image_url'); // Đường dẫn hình ánh
            $table->string('excerpt'); // Mô tả
            $table->time('published_at'); // Thoi gian phát hành
            $table->timestamps();
            $table->boolean('status')->default(true);
            $table->foreignId('category_blog_id')->nullable()->constrained('categories');
            $table->foreignId('author_id')->nullable()->constrained('users');
            $table->integer('view_count')->default(0);
            $table->string('slug')->unique();
            $table->text('content')->nullable();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blogs');
    }
};
