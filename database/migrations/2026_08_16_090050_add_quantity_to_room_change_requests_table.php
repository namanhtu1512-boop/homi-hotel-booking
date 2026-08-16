<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('room_change_requests', function (Blueprint $table) {
            // Số phòng (trong tổng quantity của dòng đơn) khách muốn đổi loại
            // — null nghĩa là đổi TOÀN BỘ dòng (hành vi cũ, giữ tương thích
            // ngược với các bản ghi trước khi có cột này).
            $table->unsignedInteger('quantity')->nullable()->after('booking_item_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_change_requests', function (Blueprint $table) {
            $table->dropColumn('quantity');
        });
    }
};
