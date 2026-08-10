<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('surcharge_items', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            // price = null cho các mục giá dao động (VD: bồi thường TV/tủ lạnh/
            // điều hòa tùy mức độ hư hỏng) — dùng price_note để gợi ý khoảng giá
            // thay vì điền sẵn số tiền, nhân viên tự nhập amount tay.
            $table->decimal('price', 12, 2)->nullable();
            $table->string('price_note')->nullable();
            $table->string('status')->default('active');
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('surcharge_items');
    }
};
