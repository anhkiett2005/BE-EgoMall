<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            if (Schema::hasColumn('reviews', 'is_visible')) {
                $table->dropColumn('is_visible');
            }

            $table->enum('status', ['pending', 'approved', 'rejected'])
                ->default('pending')
                ->after('is_anonymous')
                ->comment('Trạng thái đánh giá: pending = chờ duyệt, approved = hiển thị, rejected = từ chối');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropColumn('status');
            $table->boolean('is_visible')->default(false)->after('is_anonymous');
        });
    }
};
