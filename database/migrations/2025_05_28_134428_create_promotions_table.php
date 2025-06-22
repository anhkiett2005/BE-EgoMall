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
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('promotion_type')->comment('Loại khuyến mãi: % hoặc quà tặng');
            $table->string('discount_type')->nullable()->comment('Loại giảm: phần trăm hoặc số tiền');
            $table->decimal('discount_value', 10, 2)->nullable();
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->boolean('status')->default(true);
            $table->integer('buy_quantity')->nullable();
            $table->integer('get_quantity')->nullable();
            $table->foreignId('gift_product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('gift_product_variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
