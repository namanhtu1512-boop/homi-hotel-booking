<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nối cổng thanh toán MoMo thật (sandbox) thay cho "online_demo" mô
     * phỏng — cần lưu orderId/requestId/transId của MoMo để đối soát khi
     * return/IPN gọi lại, và payload thô để tra cứu khi có tranh chấp.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', [
                'pay_at_hotel',
                'bank_transfer',
                'online_demo',
                'cash_with_deposit',
                'momo',
            ])->default('pay_at_hotel')->change();

            $table->string('gateway_order_id')->nullable()->after('deposit_transaction_code');
            $table->string('gateway_trans_id')->nullable()->after('gateway_order_id');
            $table->text('gateway_payload')->nullable()->after('gateway_trans_id');

            $table->unique('gateway_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique(['gateway_order_id']);
            $table->dropColumn(['gateway_order_id', 'gateway_trans_id', 'gateway_payload']);

            $table->enum('method', [
                'pay_at_hotel',
                'bank_transfer',
                'online_demo',
                'cash_with_deposit',
            ])->default('pay_at_hotel')->change();
        });
    }
};
