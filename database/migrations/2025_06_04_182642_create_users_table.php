<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->bigIncrements('id');

            // Thông tin cơ bản
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');

            // Thêm các cột riêng theo ERD
            $table->string('phone')->nullable()->comment('Số điện thoại người dùng');
            $table->string('google_id')->nullable()->comment('Lưu Google OAuth ID nếu đăng nhập bằng Google');
            $table->string('facebook_id')->nullable()->comment('Lưu Facebook OAuth ID nếu đăng nhập bằng Facebook');
            $table->string('image')->nullable()->comment('URL hoặc đường dẫn avatar người dùng');

            // Quan hệ role (foreign key)
            $table->unsignedBigInteger('role_id')->default(4)->comment('Tham chiếu đến roles.id, mặc định role khách hàng = 4');

            // Trạng thái account
            $table->boolean('is_active')->default(true)->comment('Active/Inactive');
            $table->string('otp', 6)->nullable()->comment('Mã OTP xác thực (nếu cần)');
            $table->timestamp('otp_expires_at')->nullable()->comment('Thời gian hết hạn OTP hoặc verify');
            $table->unsignedTinyInteger('otp_sent_count')
                ->default(0)
                ->comment('Số lần đã gửi OTP trong khung giờ hiện tại');
            $table->timestamp('otp_sent_at')
                ->nullable()
                ->comment('Thời điểm bắt đầu khung đếm resend OTP');

            // $table->rememberToken();
            $table->timestamps();

            // Khóa ngoại: role_id tham chiếu sang bảng roles (Chưa có bảng này, sẽ tạo sau)
            $table->foreign('role_id')
                ->references('id')
                ->on('roles')
                ->onDelete('restrict')
                ->onUpdate('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
