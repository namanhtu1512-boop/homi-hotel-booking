<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Bản ghi "cần hoàn tiền" cho trường hợp VNPay báo thanh toán thành công SAU
 * KHI booking đã bị hủy (hết hạn giữ chỗ + khoảng đệm, hoặc admin hủy tay)
 * — xem BookingService::confirmVnpayReturn()/resolveOrphanedRefund(). unique
 * trên payment_id vừa là ràng buộc nghiệp vụ (1 giao dịch chỉ orphan 1 lần)
 * vừa là lưới an toàn DB-level chống IPN gọi lại nhiều lần tạo trùng bản ghi.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('refund_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('transaction_code')->nullable();
            $table->string('gateway_transaction_no')->nullable();
            $table->decimal('amount', 12, 2);
            $table->text('reason');
            $table->enum('status', ['pending', 'refunded', 'failed'])->default('pending');
            $table->json('gateway_response')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('refund_requests');
    }
};
