<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->text('description')->nullable();

            // 'promotion' = khuyến mãi áp dụng giá, 'announcement' = thông báo/tin tức
            $table->string('type', 30)->default('promotion');

            $table->date('valid_from')->nullable();
            $table->date('valid_to')->nullable();

            // null = áp dụng toàn hệ thống, không null = chỉ riêng 1 khách sạn
            $table->foreignId('hotel_id')
                ->nullable()
                ->constrained('hotels')
                ->nullOnDelete();

            $table->enum('status', ['active', 'inactive'])->default('active');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
