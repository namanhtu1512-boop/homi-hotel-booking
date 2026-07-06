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
        Schema::create('room_holds', function (Blueprint $table) {
            $table->id();

            $table->foreignId('room_type_id')
                ->constrained('room_types')
                ->cascadeOnDelete();

            $table->string('session_id');
            $table->date('check_in');
            $table->date('check_out');
            $table->unsignedInteger('quantity');
            $table->timestamp('expires_at');

            $table->timestamps();

            $table->index(['room_type_id', 'expires_at']);
            $table->index('session_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('room_holds');
    }
};
