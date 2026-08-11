<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table) {
            // price = null cho các dịch vụ chưa có bảng giá cố định (VD: thuê
            // xe theo đoàn, trang trí theo yêu cầu...) — nhân viên tự nhập số
            // tiền thực tế khi thêm, giống cơ chế price_note của SurchargeItem.
            $table->decimal('price', 12, 2)->nullable()->change();
            $table->string('price_note')->nullable()->after('price');
            $table->string('group')->nullable()->after('price_note');
        });
    }

    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn(['price_note', 'group']);
            $table->decimal('price', 12, 2)->nullable(false)->change();
        });
    }
};
