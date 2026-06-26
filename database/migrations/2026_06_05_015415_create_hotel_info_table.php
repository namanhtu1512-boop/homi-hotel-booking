<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * hotel_info — bảng singleton, luôn chỉ có đúng 1 bản ghi vì hệ thống
     * Homi chỉ vận hành 1 khách sạn duy nhất. Không có slug/city/district
     * vì không cần định danh hay tìm kiếm theo nhiều khách sạn.
     */
    public function up(): void
    {
        Schema::create('hotel_info', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('address');
            $table->text('description')->nullable();

            $table->time('check_in_time')->nullable();
            $table->time('check_out_time')->nullable();
            $table->text('policies')->nullable();

            $table->unsignedTinyInteger('star_rating')->nullable();

            $table->enum('status', ['active', 'maintenance'])
                ->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hotel_info');
    }
};
