<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['owner_id']);
            
            $table->renameColumn('owner_id', 'owner_code');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->renameColumn('owner_code', 'owner_id');
            $table->foreignId('owner_id')->change()->constrained('users');
        });
    }
};
