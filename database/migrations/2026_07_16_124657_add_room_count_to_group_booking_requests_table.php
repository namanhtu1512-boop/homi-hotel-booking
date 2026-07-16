<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('group_booking_requests', function (Blueprint $table) {
            $table->unsignedInteger('room_count')->nullable()->after('group_size');
        });
    }

    public function down(): void
    {
        Schema::table('group_booking_requests', function (Blueprint $table) {
            $table->dropColumn('room_count');
        });
    }
};
