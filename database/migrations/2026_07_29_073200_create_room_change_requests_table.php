<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('room_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained('bookings')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->foreignId('current_room_type_id')->nullable()->constrained('room_types')->nullOnDelete();
            $table->foreignId('requested_room_type_id')->nullable()->constrained('room_types')->nullOnDelete();

            $table->date('current_check_in')->nullable();
            $table->date('current_check_out')->nullable();
            $table->date('requested_check_in')->nullable();
            $table->date('requested_check_out')->nullable();

            $table->text('reason')->nullable();
            $table->string('status')->default('pending');
            $table->text('staff_note')->nullable();

            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_change_requests');
    }
};
