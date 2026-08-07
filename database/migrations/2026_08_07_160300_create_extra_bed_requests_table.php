<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('extra_bed_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('booking_id')->constrained()->cascadeOnDelete();
            $table->foreignId('booking_item_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedInteger('requested_extra_beds');
            $table->unsignedInteger('available_extra_beds');
            $table->enum('status', ['pending', 'resolved', 'waitlisted'])->default('pending');
            $table->enum('resolution', ['upgrade_room', 'add_room', 'drop_extra_bed', 'waitlist', 'staff_cancel'])->nullable();
            $table->json('suggested_alternatives')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('handled_at')->nullable();
            $table->text('staff_note')->nullable();
            $table->timestamps();

            $table->index(['booking_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('extra_bed_requests');
    }
};
