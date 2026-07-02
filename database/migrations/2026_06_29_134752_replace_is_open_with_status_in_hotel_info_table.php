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
        if (Schema::hasColumn('hotel_info', 'is_open')) {
            Schema::table('hotel_info', function (Blueprint $table) {
                $table->dropColumn('is_open');
            });
        }
        if (! Schema::hasColumn('hotel_info', 'status')) {
            Schema::table('hotel_info', function (Blueprint $table) {
                $table->enum('status', ['active', 'maintenance'])->default('active')->after('star_rating');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('hotel_info', 'status')) {
            Schema::table('hotel_info', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
        if (! Schema::hasColumn('hotel_info', 'is_open')) {
            Schema::table('hotel_info', function (Blueprint $table) {
                $table->boolean('is_open')->default(true)->after('star_rating');
            });
        }
    }
};
