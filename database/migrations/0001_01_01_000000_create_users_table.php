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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->text('phone');
            $table->string('google_id')->nullable();
            $table->string('facebook_id')->nullable();
            $table->enum('provider', ['google', 'facebook'])->nullable();
            $table->string('address');
            $table->string('image');
            $table->foreignId('role_id')->references('id')->on('roles');
            $table->boolean('is_active')->default(0);
            $table->timestamp('email_verified_at');
            $table->string('otp')->nullable();
            $table->timestamp('expries_at')->nullable();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
