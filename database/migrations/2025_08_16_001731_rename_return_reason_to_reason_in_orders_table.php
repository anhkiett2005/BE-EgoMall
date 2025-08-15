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
        Schema::table('orders', function (Blueprint $table) {
             $table->renameColumn('return_reason', 'reason');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->string('reason')->nullable()->after('status')->change();

            // Xóa cột cancel_reason
            $table->dropColumn('cancel_reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->renameColumn('reason', 'return_reason');

            $table->string('cancel_reason', 255)->nullable()->after('return_reason');
        });
    }
};
