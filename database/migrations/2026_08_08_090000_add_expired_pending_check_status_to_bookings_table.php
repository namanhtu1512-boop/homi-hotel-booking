<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm "expired_pending_check" vào enum status — trạng thái đệm khi hold giữ
 * chỗ (deposit_expires_at) đã hết hạn nhưng hệ thống CHƯA hủy/nhả phòng
 * ngay, chờ thêm services.booking.expired_grace_minutes để bù trễ IPN/return
 * VNPay tới sau khi hold đã hết hạn (race giữ chỗ vs xác nhận thanh toán —
 * xem BookingService::processBookingExpiry()). Cùng khuôn với 2 migration
 * enum trước đó. expired_pending_check_at đánh dấu thời điểm bắt đầu đệm, để
 * job tính khi nào hết hạn đệm thật sự.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'pending_deposit',
                'pending_consultation',
                'expired_pending_check',
                'confirmed',
                'cancelled',
                'checked_in',
                'checked_out',
                'completed',
            ])->default('pending')->change();

            $table->timestamp('expired_pending_check_at')->nullable()->after('deposit_expires_at');
        });
    }

    public function down(): void
    {
        DB::table('bookings')->where('status', 'expired_pending_check')->update(['status' => 'cancelled']);

        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn('expired_pending_check_at');

            $table->enum('status', [
                'pending',
                'pending_deposit',
                'pending_consultation',
                'confirmed',
                'cancelled',
                'checked_in',
                'checked_out',
                'completed',
            ])->default('pending')->change();
        });
    }
};
