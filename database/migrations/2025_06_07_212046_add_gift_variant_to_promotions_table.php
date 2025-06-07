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
        Schema::table('promotions', function (Blueprint $table) {
            $table->unsignedBigInteger('gift_product_variant_id')->nullable()->after('gift_product_id');

            $table->foreign('gift_product_variant_id')
                  ->references('id')
                  ->on('product_variants')
                  ->onDelete('set null')
                  ->onUpdate('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            // Xoá cột
            $table->dropForeign(['gift_product_variant_id']);
            $table->dropColumn('gift_product_variant_id');

        });
    }
};
