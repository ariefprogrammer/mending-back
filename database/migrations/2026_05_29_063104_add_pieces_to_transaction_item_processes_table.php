<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaction_item_processes', function (Blueprint $table) {
            $table->unsignedInteger('pieces')->default(0)->after('asset_id');
        });
    }

    public function down(): void
    {
        Schema::table('transaction_item_processes', function (Blueprint $table) {
            $table->dropColumn('pieces');
        });
    }
};