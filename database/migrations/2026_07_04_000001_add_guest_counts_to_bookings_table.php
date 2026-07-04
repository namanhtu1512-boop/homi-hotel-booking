<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Số khách lưu trú của đơn — dùng để validate theo sức chứa loại phòng
     * (adults >= 1, children >= 0). Đơn cũ mặc định 1 người lớn, 0 trẻ em.
     */
    public function up(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->unsignedSmallInteger('adults')->default(1)->after('nights');
            $table->unsignedSmallInteger('children')->default(0)->after('adults');
        });
    }

    public function down(): void
    {
        Schema::table('bookings', function (Blueprint $table) {
            $table->dropColumn(['adults', 'children']);
        });
    }
};
