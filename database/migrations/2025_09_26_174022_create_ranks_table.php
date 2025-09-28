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
        Schema::create('ranks', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->bigInteger('amount_to_point');
            $table->bigInteger('min_spent_amount');
            $table->bigInteger('converted_amount');
            $table->bigInteger('discount')->nullable();
            $table->bigInteger('maximum_discount_order')->nullable();
            $table->string('type_time_receive')->nullable();
            $table->string('time_receive_point' )->nullable();
            $table->unsignedBigInteger('minimum_point')->nullable();
            $table->unsignedBigInteger('maintenance_point')->nullable();
            $table->unsignedBigInteger('point_limit_transaction')->nullable();
            $table->boolean('status_payment_point')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ranks');
    }
};
