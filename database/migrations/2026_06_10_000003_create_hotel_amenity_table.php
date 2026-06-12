<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_amenity', function (Blueprint $table) {
            $table->foreignId('hotel_id')
                ->constrained('hotels')
                ->cascadeOnDelete();

            $table->foreignId('amenity_id')
                ->constrained('amenities')
                ->cascadeOnDelete();

            $table->primary(['hotel_id', 'amenity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_amenity');
    }
};
