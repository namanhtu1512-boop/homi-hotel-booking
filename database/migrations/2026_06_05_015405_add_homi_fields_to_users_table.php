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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 20)->nullable()->after('email');
            $table->string('address')->nullable()->after('phone');

            $table->enum('role', ['customer', 'staff', 'admin'])
                ->default('customer')
                ->after('password');

            $table->enum('status', ['active', 'locked'])
                ->default('active')
                ->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['phone', 'address', 'role', 'status']);
        });
    }
};
