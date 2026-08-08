<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('booking_item_rooms', function (Blueprint $table) {
            $table->timestamp('checked_in_at')->nullable()->after('room_id');
            $table->timestamp('checked_out_at')->nullable()->after('checked_in_at');
        });

        // Backfill cho các bản ghi tạo TRƯỚC migration này — `created_at` đã
        // luôn đúng bằng thời điểm check-in thực tế (BookingItemRoom chỉ được
        // tạo tại BookingService::checkIn()), nên dùng lại được thay vì để
        // NULL (NULL sẽ vỡ cột "Nhận / trả phòng" ở trang Phòng vật lý cho
        // các đơn đang lưu trú từ trước khi có tính năng này).
        DB::table('booking_item_rooms')->update(['checked_in_at' => DB::raw('created_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('booking_item_rooms', function (Blueprint $table) {
            $table->dropColumn(['checked_in_at', 'checked_out_at']);
        });
    }
};
