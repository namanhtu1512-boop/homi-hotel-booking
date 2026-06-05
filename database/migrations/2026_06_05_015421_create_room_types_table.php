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
        Schema::create('room_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hotel_id')
                ->constrained('hotels')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug');

            $table->text('description')->nullable();

            $table->decimal('price_per_night', 12, 2);
            $table->unsignedTinyInteger('capacity');
            $table->string('bed_type')->nullable();
            $table->decimal('area', 6, 2)->nullable();

            $table->unsignedInteger('total_rooms');

            $table->enum('status', ['active', 'hidden', 'maintenance'])
                ->default('active');

            $table->timestamps();

            $table->unique(['hotel_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_types');
    }
};
