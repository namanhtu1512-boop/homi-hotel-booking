<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hỗ trợ phương thức "Tiền mặt khi nhận phòng, đặt cọc trước 30%" —
     * `amount` vẫn là tổng tiền đơn (không đổi), các cột dưới đây lưu riêng
     * phần cọc đã thu trước qua kênh online (mô phỏng), phần còn lại thu
     * bằng tiền mặt tại khách sạn khi check-in.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('deposit_amount', 12, 2)->nullable()->after('amount');
            $table->timestamp('deposit_paid_at')->nullable()->after('paid_at');
            $table->string('deposit_transaction_code')->nullable()->after('transaction_code');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['deposit_amount', 'deposit_paid_at', 'deposit_transaction_code']);
        });
    }
};
