<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('booking_promotions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            $table->foreignId('promotion_id')
                ->nullable()
                ->constrained('promotions')
                ->nullOnDelete();

            $table->unsignedBigInteger('discount_amount');

            $table->timestamps();

            $table->unique(['booking_id', 'promotion_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('booking_promotions');
    }
};
