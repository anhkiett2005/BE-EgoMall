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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('unique_id')->unique();
            $table->foreignId('user_id')->constrained('users');
            $table->integer('total_price');
            $table->enum('status', ['ordered', 'confirmed', 'shipping', 'delivered','cancelled','return_sales'])->default('ordered');
            $table->string('note');
            $table->string('shipping_name');
            $table->string('shipping_phone');
            $table->string('shipping_email');
            $table->string('shipping_address');
            $table->string('payment_method');
            $table->timestamp('payment_created_at')->nullable();
            $table->string('payment_status');
            $table->timestamp('payment_date')->nullable();
            $table->string('transaction_id')->nullable();
            $table->foreignId('coupon_id')->nullable()->references('id')->on('coupons')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
