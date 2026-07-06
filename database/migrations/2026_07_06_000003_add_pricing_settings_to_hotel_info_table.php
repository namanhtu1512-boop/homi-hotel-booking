<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->decimal('weekend_surcharge_percent', 5, 2)->default(0)->after('policies');
            $table->unsignedBigInteger('child_surcharge_per_night')->default(0)->after('weekend_surcharge_percent');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->dropColumn(['weekend_surcharge_percent', 'child_surcharge_per_night']);
        });
    }
};
