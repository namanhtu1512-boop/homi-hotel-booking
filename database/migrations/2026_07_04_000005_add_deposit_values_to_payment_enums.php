<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cột `method`/`status` của bảng payments được khai báo bằng
     * Schema::enum() (MySQL: ENUM thật; SQLite: CHECK constraint) — thêm
     * phương thức "cash_with_deposit" và trạng thái "deposit_paid" cần mở
     * rộng danh sách giá trị hợp lệ, không chỉ thêm cột mới.
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', [
                'pay_at_hotel',
                'bank_transfer',
                'online_demo',
                'cash_with_deposit',
            ])->default('pay_at_hotel')->change();

            $table->enum('status', [
                'unpaid',
                'pending',
                'deposit_paid',
                'paid',
                'refunded',
                'failed',
            ])->default('unpaid')->change();
        });
    }

    public function down(): void
    {
        DB::table('payments')->where('method', 'cash_with_deposit')->update(['method' => 'pay_at_hotel']);
        DB::table('payments')->where('status', 'deposit_paid')->update(['status' => 'unpaid']);

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('method', [
                'pay_at_hotel',
                'bank_transfer',
                'online_demo',
            ])->default('pay_at_hotel')->change();

            $table->enum('status', [
                'unpaid',
                'pending',
                'paid',
                'refunded',
                'failed',
            ])->default('unpaid')->change();
        });
    }
};
