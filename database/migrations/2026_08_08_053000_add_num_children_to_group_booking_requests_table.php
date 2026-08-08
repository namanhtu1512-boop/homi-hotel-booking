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
            $table->unsignedInteger('num_children')->nullable()->default(0)->after('group_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_booking_requests', function (Blueprint $table) {
            $table->dropColumn('num_children');
        });
    }
};
