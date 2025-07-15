<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('review_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->comment('người phản hồi')->constrained()->onDelete('cascade');
            $table->text('reply');
            $table->timestamps();

            $table->unique('review_id'); // mỗi review chỉ có 1 phản hồi
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_replies');
    }
};