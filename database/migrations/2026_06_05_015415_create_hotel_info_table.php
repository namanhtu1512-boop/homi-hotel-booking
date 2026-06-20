<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * hotel_info là bảng singleton (luôn chỉ có đúng 1 dòng, id = 1) — Homi
     * chỉ quản lý 1 khách sạn duy nhất, không phải nền tảng đa khách sạn.
     */
    public function up(): void
    {
        Schema::create('hotel_info', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();
            $table->string('address');
            $table->string('hotline')->nullable();
            $table->string('email')->nullable();

            $table->string('check_in_time')->nullable();
            $table->string('check_out_time')->nullable();
            $table->text('policies')->nullable();

            $table->unsignedTinyInteger('star_rating')->nullable();
            $table->boolean('is_open')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_info');
    }
};
