<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_discount_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            // nullable — bậc ưu đãi tự động (type='policy_tier') có thể được
            // ghi nhận trong ngữ cảnh không có actor đăng nhập (vd tạo đơn qua
            // lệnh nền/hệ thống); user_id lúc đó để trống thay vì gán bừa.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('group_discount_policy_id')->nullable()->constrained('group_discount_policies')->nullOnDelete();

            // 'policy_tier' (tự động theo bậc số phòng) | 'staff_extra' (đề xuất/áp dụng thêm)
            $table->string('type');

            $table->unsignedInteger('room_count')->nullable();
            $table->unsignedInteger('original_subtotal');

            $table->decimal('requested_percent', 5, 2);
            $table->unsignedInteger('requested_amount');
            $table->decimal('approved_percent', 5, 2)->nullable();
            $table->unsignedInteger('approved_amount')->nullable();
            $table->decimal('staff_cap_percent_snapshot', 5, 2)->nullable();

            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->text('admin_note')->nullable();

            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();

            $table->timestamps();
            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_discount_requests');
    }
};
