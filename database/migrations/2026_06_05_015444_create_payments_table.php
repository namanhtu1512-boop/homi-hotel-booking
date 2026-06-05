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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('booking_id')
                ->constrained('bookings')
                ->cascadeOnDelete();

            $table->enum('method', [
                'pay_at_hotel',
                'bank_transfer',
                'online_demo'
            ])->default('pay_at_hotel');

            $table->decimal('amount', 12, 2);

            $table->enum('status', [
                'unpaid',
                'pending',
                'paid',
                'refunded',
                'failed'
            ])->default('unpaid');

            $table->string('transaction_code')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('note')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
