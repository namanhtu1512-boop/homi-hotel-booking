<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incidental_invoice_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incidental_invoice_id')->constrained('incidental_invoices')->cascadeOnDelete();
            $table->string('type');
            $table->string('description');
            $table->decimal('amount', 12, 2);
            $table->foreignId('booking_service_id')->nullable()->constrained('booking_services')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('incidental_invoice_items');
    }
};
