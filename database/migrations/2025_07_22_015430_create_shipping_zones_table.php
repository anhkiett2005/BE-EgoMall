<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('shipping_zones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_method_id')
                  ->constrained('shipping_methods')
                  ->onDelete('cascade');

            $table->string('province_code', 20)->collation('utf8mb4_general_ci');

            $table->decimal('fee', 10, 2);
            $table->boolean('is_available')->default(true);

            $table->timestamps();

            $table->foreign('province_code')
                  ->references('code')
                  ->on('provinces')
                  ->onDelete('cascade');

            $table->unique(['shipping_method_id', 'province_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_zones');
    }
};
