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
        Schema::create('booking_item_guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_item_id')->constrained()->cascadeOnDelete();

            // Tùy chọn lúc khách tự đặt online (điền được bao nhiêu hay bấy
            // nhiêu, không chặn submit) — nhưng BẮT BUỘC điền đủ (full_name +
            // id_number) cho từng khách trước khi lễ tân cho check-in (xem
            // BookingService::checkIn()), khớp quy định khai báo tạm trú thực
            // tế của khách sạn tại Việt Nam.
            $table->string('full_name')->nullable();
            $table->string('id_number')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_item_guests');
    }
};
