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
        Schema::table('late_checkout_requests', function (Blueprint $table) {
            // Mảng booking_item_room_id (phòng VẬT LÝ, khác
            // early_checkin_requests.room_selections là map số lượng — ở đó
            // chưa check-in nên chưa có phòng vật lý cụ thể) — xem
            // LateCheckoutRequest::selectedBookingItemRooms(). Phí % giá
            // phòng được tính lại chỉ trên đúng các phòng đã chọn ở đây (xem
            // LateCheckoutRequestService::lastNightTotal()), KHÁC với phí
            // nhận phòng sớm (cố định, không phụ thuộc số phòng chọn).
            $table->json('room_selections')->nullable()->after('reason');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('late_checkout_requests', function (Blueprint $table) {
            $table->dropColumn('room_selections');
        });
    }
};
