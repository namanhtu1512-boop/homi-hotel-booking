<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->softDeletes()->after('status');
        });

        Schema::table('room_types', function (Blueprint $table) {
            $table->softDeletes()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('hotels', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });

        Schema::table('room_types', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
