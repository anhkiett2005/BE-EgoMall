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
            $table->string('slug')->unique(); // Đường dẫn URL thân thiện
            $table->foreignId('author_id')->constrained('users'); // FK đến users
            $table->boolean('status')->default(true);
            $table->timestamp('published_at')->nullable(); // Thời gian xuất bản
            $table->timestamp('updated_at'); // created_at & updated_at
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
