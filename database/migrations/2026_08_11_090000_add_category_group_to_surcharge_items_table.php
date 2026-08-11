<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('surcharge_items', function (Blueprint $table) {
            // Toàn bộ 19 dòng có sẵn hiện là đồ hỏng/mất, default 'damage' để
            // backfill chúng mà không cần chỉnh tay.
            $table->string('category')->default('damage')->after('price_note');
            $table->string('group')->nullable()->after('category');
        });
    }

    public function down(): void
    {
        Schema::table('surcharge_items', function (Blueprint $table) {
            $table->dropColumn(['category', 'group']);
        });
    }
};
