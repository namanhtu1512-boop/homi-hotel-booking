<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phụ thu giường phụ (VNĐ/giường/đêm) — cùng khuôn với
 * child_surcharge_per_night, tính trong PricingService::calculate().
 * Default khác 0 (không giống child_surcharge_per_night mặc định 0) vì thu
 * phí giường phụ là kỳ vọng mặc định của nghiệp vụ khách sạn thật, không
 * phải tùy chọn admin phải tự bật lên mới có tác dụng.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->unsignedInteger('extra_bed_surcharge_per_night')->default(200000)->after('extra_beds_total');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->dropColumn('extra_bed_surcharge_per_night');
        });
    }
};
