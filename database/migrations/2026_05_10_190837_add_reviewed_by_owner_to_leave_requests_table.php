<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->uuid('reviewed_by_owner')->nullable()->after('reviewed_by');
            $table->foreign('reviewed_by_owner')
                  ->references('id')
                  ->on('users')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('leave_requests', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by_owner']);
            $table->dropColumn('reviewed_by_owner');
        });
    }
};