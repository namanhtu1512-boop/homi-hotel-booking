<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Phụ phí phát sinh (hư hỏng, trả phòng trễ...) ghi nhận lúc thanh toán —
     * khác với dịch vụ thêm (booking_services, gắn với catalog `services`),
     * đây là khoản tùy ý admin/staff nhập tay kèm lý do. Cộng dồn vào
     * payments.amount/bookings.total_amount (xem BookingService::addSurcharge()).
     */
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->decimal('surcharge_amount', 12, 2)->default(0)->after('deposit_amount');
            $table->string('surcharge_note')->nullable()->after('surcharge_amount');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['surcharge_amount', 'surcharge_note']);
        });
    }
};
