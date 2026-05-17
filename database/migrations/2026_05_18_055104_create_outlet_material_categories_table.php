<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_material_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->string('name', 100);
            $table->timestamps();

            $table->index('outlet_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_material_categories');
    }
};