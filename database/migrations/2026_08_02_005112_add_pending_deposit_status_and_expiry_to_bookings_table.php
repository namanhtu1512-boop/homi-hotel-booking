<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cột `status` của bảng bookings được khai báo bằng Schema::enum()
     * (MySQL: ENUM thật; SQLite: CHECK constraint) — thêm trạng thái mới
     * "pending_deposit" (chờ khách đặt cọc/thanh toán trong khung giờ giữ
     * chỗ, xem BookingService::DEPOSIT_HOLD_MINUTES) cần mở rộng danh sách
     * giá trị hợp lệ, không chỉ thêm cột mới. Thêm luôn deposit_expires_at
     * để lưu hạn giữ chỗ của đơn.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'pending_deposit',
                'confirmed',
                'cancelled',
                'checked_in',
                'checked_out',
                'completed',
            ])->default('pending')->change();

            $table->timestamp('deposit_expires_at')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        DB::table('bookings')->where('status', 'pending_deposit')->update(['status' => 'cancelled']);

        Schema::table('bookings', function (Blueprint $table) {
            $table->enum('status', [
                'pending',
                'confirmed',
                'cancelled',
                'checked_in',
                'checked_out',
                'completed',
            ])->default('pending')->change();

            $table->dropColumn('deposit_expires_at');
        });
    }
};
