<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_method_id')->nullable()->after('shipping_address');
            $table->decimal('shipping_fee', 10, 2)->default(0)->after('shipping_method_id');
            $table->string('shipping_method_snapshot')->nullable()->after('shipping_fee');

            $table->foreign('shipping_method_id')
                ->references('id')
                ->on('shipping_methods')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['shipping_method_id']);
            $table->dropColumn(['shipping_method_id', 'shipping_fee', 'shipping_method_snapshot']);
        });
    }
};