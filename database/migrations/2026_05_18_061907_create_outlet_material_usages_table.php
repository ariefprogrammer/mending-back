<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_material_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_material_id')->constrained('outlet_materials')->cascadeOnDelete();
            $table->foreignId('satuan_id')->nullable()->constrained('satuans')->nullOnDelete();
            $table->float('quantity_used_per_unit');
            $table->timestamps();

            $table->index('outlet_material_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_material_usages');
    }
};