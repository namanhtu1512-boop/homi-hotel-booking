<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Danh sách chờ — phòng khách muốn giữ lại để đặt sau, gắn theo tài
     * khoản (không dùng session) để còn nguyên khi đăng xuất/đổi thiết bị.
     * Unique (user_id, room_type_id): thêm lại loại phòng đã có ⇒ cộng dồn
     * quantity ở tầng service, không tạo dòng trùng.
     */
    public function up(): void
    {
        Schema::create('wishlist_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->foreignId('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->unsignedSmallInteger('adults')->default(1);
            $table->unsignedSmallInteger('children')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'room_type_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wishlist_items');
    }
};
