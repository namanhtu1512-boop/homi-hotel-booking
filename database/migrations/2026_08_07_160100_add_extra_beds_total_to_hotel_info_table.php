<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->unsignedInteger('extra_beds_total')->default(5)->after('cancellation_hours_before');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->dropColumn('extra_beds_total');
        });
    }
};
