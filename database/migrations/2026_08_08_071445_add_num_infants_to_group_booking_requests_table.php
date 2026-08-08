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
        Schema::table('group_booking_requests', function (Blueprint $table) {
            // num_children (đã có) từ giờ mang nghĩa "trẻ em 6-11 tuổi", khớp
            // đúng quy ước `children`/`infants` của hệ thống đặt phòng lẻ.
            $table->unsignedInteger('num_infants')->nullable()->default(0)->after('num_children');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_booking_requests', function (Blueprint $table) {
            $table->dropColumn('num_infants');
        });
    }
};
