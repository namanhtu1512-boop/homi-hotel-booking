<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Số giường phụ ĐÃ ĐƯỢC CẤP cho dòng loại phòng này — chỉ khác 0 khi đã xác
 * nhận có đủ giường phụ (BookingService::create()) hoặc sau khi
 * ExtraBedRequestService::resolve() duyệt. Booking đang `pending_consultation`
 * giữ nguyên 0 ở đây (chưa chắc được cấp) nên không tự chiếm pool khi
 * ExtraBedInventoryService đếm tồn kho.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->unsignedInteger('extra_beds')->default(0)->after('infants');
        });
    }

    public function down(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->dropColumn('extra_beds');
        });
    }
};
