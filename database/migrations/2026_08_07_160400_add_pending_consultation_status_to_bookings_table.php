<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Thêm "pending_consultation" (chờ tư vấn — giường phụ không đủ khi tạo đơn,
 * xem BookingService::create(), ExtraBedRequestService) vào enum status —
 * cùng khuôn với add_pending_deposit_status_and_expiry_to_bookings_table.php.
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
                'confirmed',
                'cancelled',
                'checked_in',
                'checked_out',
                'completed',
            ])->default('pending')->change();
        });
    }

    public function down(): void
    {
        DB::table('bookings')->where('status', 'pending_consultation')->update(['status' => 'cancelled']);

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
        });
    }
};
