<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();

            // Khách hàng mà hội thoại này thuộc về — "cuộc hội thoại" được
            // xác định ngầm bằng cột này, không có model Conversation riêng.
            $table->foreignId('customer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // Người GỬI tin nhắn này — có thể chính là customer_id (khách tự
            // nhắn) hoặc bất kỳ admin/staff nào (hộp thư dùng chung).
            $table->foreignId('sender_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->text('body');
            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};
