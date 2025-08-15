<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('setting_key')->unique(); // ví dụ: site_name, email_host
            $table->longText('setting_value')->nullable();

            // enum để FE render form + BE validate/cast theo type
            $table->enum('setting_type', [
                'string','text','boolean','number','email','url','image','password','json'
            ])->default('string');

            // nhóm hiển thị tab
            $table->enum('setting_group', [
                'general','email','system','contact','seo','chatbot','integrations'
            ])->default('general');

            $table->string('setting_label')->nullable(); // tiêu đề đẹp cho UI
            $table->text('description')->nullable();

            $table->timestamps();

            $table->index('setting_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_settings');
    }
};
