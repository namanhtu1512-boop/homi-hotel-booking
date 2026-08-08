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
            $table->json('selected_suggestion')->nullable()->after('room_type_ids');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('group_booking_requests', function (Blueprint $table) {
            $table->dropColumn('selected_suggestion');
        });
    }
};
