<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hotel_info_images', function (Blueprint $table) {
            $table->id();

            $table->foreignId('hotel_info_id')
                ->constrained('hotel_info')
                ->cascadeOnDelete();

            $table->string('path');
            $table->unsignedTinyInteger('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_info_images');
    }
};
