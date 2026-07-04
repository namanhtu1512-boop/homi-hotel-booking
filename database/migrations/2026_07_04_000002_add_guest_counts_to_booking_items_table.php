<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Số khách được validate theo TỪNG loại phòng (capacity riêng), không
     * còn gộp chung ở cấp đơn — bookings.adults/children giữ lại làm tổng
     * hiển thị nhanh, được tính bằng tổng các dòng ở đây.
     */
    public function up(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->unsignedSmallInteger('adults')->default(1)->after('quantity');
            $table->unsignedSmallInteger('children')->default(0)->after('adults');
        });
    }

    public function down(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->dropColumn(['adults', 'children']);
        });
    }
};
