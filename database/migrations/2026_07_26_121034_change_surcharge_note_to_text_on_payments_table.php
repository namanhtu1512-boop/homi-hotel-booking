<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // surcharge_note tích lũy nhiều dòng phụ phí nối chuỗi lại
        // (BookingService::addSurcharge()) — VARCHAR(255) tràn ngay từ phụ
        // phí thứ 2 trở đi (mỗi lý do đã cho phép tới 255 ký tự), gây lỗi
        // "Data too long" khi thêm phụ phí thứ 2+ cho cùng 1 đơn.
        Schema::table('payments', function (Blueprint $table) {
            $table->text('surcharge_note')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('surcharge_note')->nullable()->change();
        });
    }
};
