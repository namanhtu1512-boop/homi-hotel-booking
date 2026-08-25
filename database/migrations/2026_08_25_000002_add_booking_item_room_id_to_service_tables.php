<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_services', function (Blueprint $table) {
            $table->foreignId('booking_item_room_id')->nullable()->after('booking_id')
                ->constrained('booking_item_rooms')->nullOnDelete();
        });

        Schema::table('incidental_invoice_items', function (Blueprint $table) {
            $table->foreignId('booking_item_room_id')->nullable()->after('incidental_invoice_id')
                ->constrained('booking_item_rooms')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('booking_services', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_item_room_id');
        });

        Schema::table('incidental_invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('booking_item_room_id');
        });
    }
};
