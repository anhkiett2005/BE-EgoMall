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
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('order_detail_id')->constrained()->onDelete('cascade');
            $table->tinyInteger('rating')->unsigned()->comment('1 đến 5 sao');
            $table->text('comment')->nullable();
            $table->boolean('is_anonymous')->default(false)->comment('Ẩn danh');
            $table->timestamps();

            $table->unique(['user_id', 'order_detail_id']); // 1 user chỉ review 1 lần / 1 sản phẩm trong đơn
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};