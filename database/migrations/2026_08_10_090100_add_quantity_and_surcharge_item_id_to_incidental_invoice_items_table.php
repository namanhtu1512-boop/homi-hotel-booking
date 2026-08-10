<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('incidental_invoice_items', function (Blueprint $table) {
            $table->unsignedInteger('quantity')->default(1)->after('amount');
            $table->foreignId('surcharge_item_id')->nullable()->after('booking_service_id')
                ->constrained('surcharge_items')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('incidental_invoice_items', function (Blueprint $table) {
            $table->dropConstrainedForeignId('surcharge_item_id');
            $table->dropColumn('quantity');
        });
    }
};
