<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // booking_id và room_type_id đang dựa vào index kép cũ để làm chỗ
        // tựa cho 2 khóa ngoại — phải có index riêng cho từng cột trước khi
        // xóa index kép, nếu không MySQL báo lỗi 1553 (index đang phục vụ FK).
        Schema::table('reviews', function (Blueprint $table) {
            $table->index('room_type_id');
            $table->unique('booking_id');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['booking_id', 'room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->unique(['booking_id', 'room_type_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique('reviews_booking_id_unique');
            $table->dropIndex(['room_type_id']);
        });
    }
};
