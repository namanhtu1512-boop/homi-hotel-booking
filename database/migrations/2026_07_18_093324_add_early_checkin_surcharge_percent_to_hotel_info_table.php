<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * % giá phòng/đêm đầu tiên tính phụ phí khi khách nhận phòng TRƯỚC giờ
     * check_in_time tiêu chuẩn — 0 (mặc định) nghĩa là không thu phí nhận
     * phòng sớm. Xem BookingService::checkIn().
     */
    public function up(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->decimal('early_checkin_surcharge_percent', 5, 2)->default(0)->after('child_surcharge_per_night');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->dropColumn('early_checkin_surcharge_percent');
        });
    }
};
