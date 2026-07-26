<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `transaction_code` lưu mã giao dịch do HỆ THỐNG mình sinh ra (vnp_TxnRef)
     * để tra cứu đơn khi cổng gọi về — còn `gateway_transaction_no`/`gateway_paid_at`
     * lưu mã giao dịch và thời điểm THẬT do cổng (VNPay) trả về, bắt buộc phải
     * gửi lại khi gọi API hoàn tiền (refund) sau này.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('gateway_transaction_no')->nullable()->after('transaction_code');
            $table->timestamp('gateway_paid_at')->nullable()->after('gateway_transaction_no');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['gateway_transaction_no', 'gateway_paid_at']);
        });
    }
};
