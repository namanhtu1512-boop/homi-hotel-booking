<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `category` nhận diện loại phòng theo NGHIỆP VỤ (standard/superior/deluxe/
 * family/suite) — tách khỏi `name` (free-text, admin đổi được) để logic sức
 * chứa/giường phụ (BookingService) không bị gãy khi đổi tên hiển thị. Backfill
 * ngay 5 dòng seed sẵn theo đúng slug hiện có.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->string('category')->nullable()->after('name');
        });

        DB::table('room_types')->where('slug', 'phong-standard')->update(['category' => 'standard']);
        DB::table('room_types')->where('slug', 'phong-superior')->update(['category' => 'superior']);
        DB::table('room_types')->where('slug', 'phong-deluxe')->update(['category' => 'deluxe']);
        DB::table('room_types')->where('slug', 'phong-family')->update(['category' => 'family']);
        DB::table('room_types')->where('slug', 'phong-suite')->update(['category' => 'suite']);
    }

    public function down(): void
    {
        Schema::table('room_types', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
