<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bỏ tính năng "Yêu cầu nhận phòng sớm" (luồng khách gửi yêu cầu + staff/admin
 * duyệt, phí cố định 100k/giờ) — nhận phòng sớm giờ hoàn toàn tự do, không
 * cần duyệt, không thu phí. Xóa luôn cột early_checkin_surcharge_percent
 * (cơ chế phụ phí % tự động cũ, đã bị luồng duyệt trên che khuất/vô hiệu hóa
 * từ lâu, xem BookingService::guardEarlyCheckinApproval()) — không hồi sinh
 * lại cơ chế % này, theo đúng tiền lệ đã làm với late_checkout_surcharge_percent
 * (xem 2026_08_01_090100_drop_late_checkout_surcharge_percent_from_hotel_info_table).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('early_checkin_requests');

        Schema::table('hotel_info', function (Blueprint $table) {
            $table->dropColumn('early_checkin_surcharge_percent');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->decimal('early_checkin_surcharge_percent', 5, 2)->default(0)->after('child_surcharge_per_night');
        });

        Schema::create('early_checkin_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->time('requested_arrival_time');
            $table->unsignedInteger('hours_early');
            $table->decimal('fee_amount', 10, 2)->default(0);
            $table->text('reason')->nullable();
            $table->json('room_selections')->nullable();
            $table->string('status')->default('pending');
            $table->text('staff_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->timestamps();
        });
    }
};
