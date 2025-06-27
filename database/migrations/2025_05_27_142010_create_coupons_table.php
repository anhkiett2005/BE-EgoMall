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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code');
            $table->string('description');
            $table->string('discount_type');
            $table->string('discount_value');
            $table->integer('min_order_value');
            $table->integer('max_discount')->nullable();
            $table->integer('usage_limit');
            $table->integer('discount_limit');
            $table->timestamp('start_date');
            $table->timestamp('end_date');
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
