<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_change_requests', function (Blueprint $table) {
            $table->foreignId('booking_item_id')->nullable()->after('booking_id')
                ->constrained('booking_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('room_change_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_item_id');
        });
    }
};
