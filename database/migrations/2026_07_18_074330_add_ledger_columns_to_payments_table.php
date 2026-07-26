<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sửa lỗi: trước đây `amount` vừa là "tổng tiền đang nợ" vừa được dùng
     * làm "số tiền đã thu qua cổng" khi hoàn tiền/thanh toán lại — khi có
     * phụ phí phát sinh SAU khi đã trả qua VNPay, `amount` tăng lên nhưng hệ
     * thống không phân biệt được phần nào đã thu qua cổng thật, dẫn tới thu
     * tiền 2 lần (thanh toán lại tính cả phần cũ) và hoàn tiền sai số (hoàn
     * cả phần thu bằng tiền mặt ngoài cổng).
     *
     * `amount_collected`: tổng số tiền ĐÃ thu qua cổng VNPay tính đến hiện
     * tại (chỉ phần thật sự captured, không tính tiền mặt/chuyển khoản thu
     * ngoài cổng cho phần phát sinh).
     * `pending_gateway_amount`: số tiền đang chờ VNPay xác nhận cho lần
     * redirect gần nhất — dùng để đối chiếu vnp_Amount trả về đúng số tiền
     * đã yêu cầu, tránh tin nhầm 1 callback báo số tiền khác.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('amount_collected', 12, 2)->default(0)->after('amount');
            $table->decimal('pending_gateway_amount', 12, 2)->nullable()->after('amount_collected');
        });

        // Dữ liệu cũ: các payment VNPay đã PAID trước migration này giả định
        // đã thu đủ `amount` qua cổng (không có cách nào biết chính xác hơn
        // với dữ liệu demo/test hiện có).
        DB::table('payments')
            ->where('method', 'online_vnpay')
            ->where('status', 'paid')
            ->update(['amount_collected' => DB::raw('amount')]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['amount_collected', 'pending_gateway_amount']);
        });
    }
};
