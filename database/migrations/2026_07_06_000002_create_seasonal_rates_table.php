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
        Schema::create('seasonal_rates', function (Blueprint $table) {
            $table->id();

            // null = áp dụng cho tất cả loại phòng.
            $table->foreignId('room_type_id')
                ->nullable()
                ->constrained('room_types')
                ->cascadeOnDelete();

            $table->string('label');
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('adjustment_type', ['percent', 'fixed_per_night']);
            $table->decimal('adjustment_value', 12, 2);
            $table->string('status')->default('active');

            $table->timestamps();

            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasonal_rates');
    }
};
