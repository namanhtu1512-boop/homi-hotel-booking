<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phân biệt "trẻ sơ sinh" (0-5 tuổi, miễn phí, KHÔNG tính vào sức chứa
     * phòng) với "trẻ em" (6-11 tuổi, cột `children` có sẵn — tính phụ thu
     * + tính vào sức chứa như từ trước giờ). Xem BookingService::create()/
     * createByAdmin() cho validate liên quan.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('infants')->default(0)->after('children');
        });

        Schema::table('booking_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('infants')->default(0)->after('children');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('infants');
        });

        Schema::table('booking_items', function (Blueprint $table) {
            $table->dropColumn('infants');
        });
    }
};
