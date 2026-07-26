<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->decimal('late_checkout_surcharge_percent', 5, 2)->default(0)->after('early_checkin_surcharge_percent');
        });
    }

    public function down(): void
    {
        Schema::table('hotel_info', function (Blueprint $table) {
            $table->dropColumn('late_checkout_surcharge_percent');
        });
    }
};
