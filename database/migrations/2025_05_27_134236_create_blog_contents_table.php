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
        Schema::create('blog_contents', function (Blueprint $table) {
            $table->id(); // Primary key
            $table->foreignId('blog_id')->constrained('blogs'); // FK đến blogs
            $table->integer('order')->default(0); // Thứ tự hiển thị
            $table->string('title'); // Tiêu đề của đoạn nội dung
            $table->text('content'); // Nội dung chi tiết
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('blog_contents');
    }
};
