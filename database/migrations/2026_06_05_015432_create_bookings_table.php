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
        Schema::create('bookings', function (Blueprint $table) {
            $table->id();

            $table->string('booking_code')->unique();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('hotel_id')
                ->constrained('hotels')
                ->cascadeOnDelete();

            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedSmallInteger('nights');

            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone', 20);

            $table->decimal('total_amount', 12, 2)->default(0);

            $table->enum('status', [
                'pending',
                'confirmed',
                'cancelled',
                'checked_in',
                'checked_out',
                'completed'
            ])->default('pending');

            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bookings');
    }
};
