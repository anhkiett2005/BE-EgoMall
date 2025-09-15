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
        Schema::table('order_details', function (Blueprint $table) {
            // Xoá ràng buộc cũ
            $table->dropForeign(['order_id']);

            // Thêm lại ràng buộc mới với cascade
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders')
                  ->onUpdate('cascade')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            // Xoá ràng buộc mới
            $table->dropForeign(['order_id']);

            // Thêm lại ràng buộc cũ (không cascade)
            $table->foreign('order_id')
                  ->references('id')
                  ->on('orders');
        });
    }
};
