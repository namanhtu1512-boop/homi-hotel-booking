<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Thêm phương thức "online_vnpay" (thanh toán online qua cổng VNPay
     * sandbox thật, thay cho "online_demo" mô phỏng) vào danh sách giá trị
     * hợp lệ của cột `method` — cùng cách mở rộng ENUM như migration
     * add_deposit_values_to_payment_enums.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', [
                'pay_at_hotel',
                'bank_transfer',
                'online_demo',
                'cash_with_deposit',
                'online_vnpay',
            ])->default('pay_at_hotel')->change();
        });
    }

    public function down(): void
    {
        DB::table('payments')->where('method', 'online_vnpay')->update(['method' => 'pay_at_hotel']);

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', [
                'pay_at_hotel',
                'bank_transfer',
                'online_demo',
                'cash_with_deposit',
            ])->default('pay_at_hotel')->change();
        });
    }
};
