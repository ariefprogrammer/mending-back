<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_notification_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')
                  ->constrained('outlets')
                  ->onDelete('cascade');
            $table->string('type', 50);
            $table->boolean('is_active')->default(false);
            $table->text('message')->nullable();
            $table->timestamps();

            $table->unique(['outlet_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_notification_templates');
    }
};