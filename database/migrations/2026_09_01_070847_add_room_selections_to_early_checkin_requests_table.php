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
        Schema::table('early_checkin_requests', function (Blueprint $table) {
            // Map booking_item_id => số phòng của dòng đó khách chọn nhận
            // sớm (VD {"92": 1, "93": 1} — chỉ 1/2 phòng Standard của dòng
            // 92). null/rỗng = áp dụng cho TOÀN BỘ phòng trong đơn (tương
            // thích ngược) — xem EarlyCheckinRequest::selectedRoomLines().
            $table->json('room_selections')->nullable()->after('reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('early_checkin_requests', function (Blueprint $table) {
            $table->dropColumn('room_selections');
        });
    }
};
