<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->decimal('child_surcharge', 12, 2)->default(0)->after('subtotal');
            $table->json('price_breakdown')->nullable()->after('child_surcharge');
        });
    }

    public function down(): void
    {
        Schema::table('booking_items', function (Blueprint $table) {
            $table->dropColumn(['child_surcharge', 'price_breakdown']);
        });
    }
};
