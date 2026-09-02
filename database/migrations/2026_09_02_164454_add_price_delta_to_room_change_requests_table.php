<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_change_requests', function (Blueprint $table) {
            // Chênh lệch tiền phòng (có dấu, dương = tăng) tại đúng thời điểm
            // DUYỆT — lưu lại ở đây (thay vì chỉ nằm trong text tự do của
            // PaymentStatusLog) để trang chi tiết đơn hiển thị được "tiền
            // phòng trước/sau khi đổi" chính xác, không cần suy diễn ngược từ
            // giá phòng hiện tại (có thể đã đổi giá theo mùa từ lúc đó).
            // Null với các bản ghi cũ tạo trước cột này (chưa có dữ liệu).
            $table->decimal('price_delta', 12, 2)->nullable()->after('requested_check_out');
        });
    }

    public function down(): void
    {
        Schema::table('room_change_requests', function (Blueprint $table) {
            $table->dropColumn('price_delta');
        });
    }
};
