<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sửa lỗi: `transaction_code`/`gateway_transaction_no` chỉ lưu được giao
     * dịch VNPay GẦN NHẤT — nếu một payment trải qua NHIỀU chu kỳ thanh toán
     * VNPay riêng biệt (VD: trả đủ lần 1 → phụ phí phát sinh mở lại PENDING →
     * trả tiếp phần chênh lệch qua VNPay lần 2), `amount_collected` là tổng
     * cộng dồn của CẢ 2 lần nhưng transaction_code/gateway_transaction_no chỉ
     * còn trỏ tới giao dịch thứ 2 — nếu hủy đơn, attemptRefund() sẽ yêu cầu
     * VNPay hoàn toàn bộ amount_collected (gồm cả tiền của giao dịch 1) NHƯNG
     * gắn vào transaction_code của giao dịch 2, vượt quá số tiền giao dịch đó
     * thực thu — VNPay sẽ từ chối hoặc xử lý sai.
     *
     * `last_gateway_amount`: số tiền THẬT SỰ đã thu qua giao dịch VNPay đang
     * được lưu ở transaction_code/gateway_transaction_no hiện tại (không phải
     * tổng cộng dồn) — dùng để giới hạn số tiền yêu cầu hoàn qua API, tránh
     * yêu cầu hoàn nhiều hơn số giao dịch đó thực nhận.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('last_gateway_amount', 12, 2)->nullable()->after('amount_collected');
        });

        // Dữ liệu cũ: giả định giao dịch VNPay gần nhất đã thu đúng bằng toàn
        // bộ amount_collected hiện có (không có cách nào biết chính xác hơn
        // với dữ liệu demo/test hiện có, và hầu hết payment chỉ trải qua 1
        // chu kỳ thanh toán).
        DB::table('payments')
            ->where('method', 'online_vnpay')
            ->where('amount_collected', '>', 0)
            ->update(['last_gateway_amount' => DB::raw('amount_collected')]);
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn('last_gateway_amount');
        });
    }
};
