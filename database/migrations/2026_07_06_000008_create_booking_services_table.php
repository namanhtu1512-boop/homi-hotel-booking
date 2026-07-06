<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_services', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            // Service dùng soft-delete nên hàng này không bao giờ thật sự bị
            // xóa trong vận hành bình thường — restrictOnDelete chỉ để chặn
            // force-delete vô tình phá vỡ lịch sử đơn đã có dịch vụ.
            $table->foreignId('service_id')
                ->constrained('services')
                ->restrictOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('subtotal', 12, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_services');
    }
};
