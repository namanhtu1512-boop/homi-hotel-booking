<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * hotel_info không cần soft delete vì là bản ghi singleton, không bao
     * giờ bị xóa — chỉ room_types mới cần soft delete (xóa loại phòng).
     */
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->softDeletes()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
