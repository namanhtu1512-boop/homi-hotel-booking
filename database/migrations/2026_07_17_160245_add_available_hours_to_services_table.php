<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Khung giờ phục vụ dịch vụ (VD: Ăn sáng buffet 06:00-10:00) — cả 2 cột
     * đều nullable, để trống nghĩa là dịch vụ phục vụ cả ngày, không giới
     * hạn giờ (VD: khăn tắm thêm, giặt ủi...). Xem Service::isAvailableAt().
     */
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->time('available_from')->nullable()->after('price');
            $table->time('available_until')->nullable()->after('available_from');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['available_from', 'available_until']);
        });
    }
};
