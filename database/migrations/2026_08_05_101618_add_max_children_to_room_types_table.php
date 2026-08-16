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
        Schema::table('room_types', function (Blueprint $table) {
            // `capacity` (đổi ý nghĩa từ đây trở đi) = sức chứa NGƯỜI LỚN cơ bản
            // của loại phòng; trẻ em (6-11 tuổi) giờ là khách THÊM ngoài sức
            // chứa đó, tối đa max_children/phòng, luôn phát sinh phụ phí
            // (hotel_info.child_surcharge_per_night, đã có sẵn) — thay cho
            // hằng số toàn cục BookingService::MAX_CHILDREN_PER_ROOM trước đây.
            $table->unsignedTinyInteger('max_children')->default(1)->after('capacity');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn('max_children');
        });
    }
};
