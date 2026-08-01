<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phụ phí trả phòng muộn không còn tính theo % cấu hình chung nữa — thay
 * bằng bảng phí cố định theo bậc giờ trễ, gắn với luồng duyệt yêu cầu
 * (xem LateCheckoutRequestService::calculateFee()). Cột này trước đây cũng
 * không hề được tính vào công thức phí thực tế (BookingService dùng hằng số
 * riêng LATE_CHECKOUT_PERCENT_PER_HOUR, không đọc cột này) nên xóa an toàn.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->dropColumn('late_checkout_surcharge_percent');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->decimal('late_checkout_surcharge_percent', 5, 2)->default(0)->after('early_checkin_surcharge_percent');
        });
    }
};
