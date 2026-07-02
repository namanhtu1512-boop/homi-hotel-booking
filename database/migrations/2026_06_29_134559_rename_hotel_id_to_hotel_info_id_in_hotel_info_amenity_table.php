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
        if (Schema::hasColumn('hotel_info_amenity', 'hotel_id')) {
            Schema::table('hotel_info_amenity', function (Blueprint $table) {
                $table->renameColumn('hotel_id', 'hotel_info_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hotel_info_amenity', 'hotel_info_id')) {
            Schema::table('hotel_info_amenity', function (Blueprint $table) {
                $table->renameColumn('hotel_info_id', 'hotel_id');
            });
        }
    }
};
