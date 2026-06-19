<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bảng cấu hình dạng key-value đơn giản — đủ cho các cờ/giá trị cấu hình
     * hệ thống (tên website, hotline, phương thức thanh toán...) mà không cần
     * một bảng riêng cho từng nhóm cấu hình.
     */
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
