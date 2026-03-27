<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->string('role')->default('staff')->after('phone');
            $table->foreignId('owner_id')->nullable()->after('role')->constrained('users')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Hapus foreign key dulu baru kolomnya
            $table->dropForeign(['owner_id']);
            $table->dropColumn(['phone', 'role', 'owner_id']);
        });
    }
};
