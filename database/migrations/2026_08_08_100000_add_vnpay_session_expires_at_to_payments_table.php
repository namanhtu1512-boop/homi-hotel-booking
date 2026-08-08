<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mốc hết hạn phiên thanh toán VNPay HIỆN TẠI (khớp đúng vnp_ExpireDate vừa
 * gửi cho VNPay ở lần initiateVnpayPayment() gần nhất) — dùng để trang Homi
 * (customer/bookings/show.blade.php) hiển thị đồng hồ đếm ngược bám theo
 * đúng phiên VNPay khách đang thấy, thay vì đếm riêng theo tổng thời gian
 * giữ chỗ (deposit_expires_at, khác khái niệm, dễ gây cảm giác lệch nhau).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->timestamp('vnpay_session_expires_at')->nullable()->after('pending_gateway_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('vnpay_session_expires_at');
        });
    }
};
