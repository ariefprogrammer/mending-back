<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('image_leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')
                  ->constrained('leave_requests')
                  ->cascadeOnDelete();
            $table->string('image_url');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('image_leave_requests');
    }
};