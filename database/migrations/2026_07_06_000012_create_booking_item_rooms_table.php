<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_item_rooms', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_item_id')
                ->constrained('booking_items')
                ->cascadeOnDelete();

            $table->foreignId('room_id')
                ->constrained('rooms')
                ->restrictOnDelete();

            $table->timestamps();

            $table->unique(['booking_item_id', 'room_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_item_rooms');
    }
};
