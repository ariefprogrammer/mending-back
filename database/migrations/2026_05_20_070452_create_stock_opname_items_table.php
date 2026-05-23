<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('outlet_material_id')->nullable()->constrained('outlet_materials')->nullOnDelete();
            $table->bigInteger('system_quantity')->default(0);
            $table->bigInteger('physical_quantity')->default(0);
            $table->bigInteger('difference')->default(0);
            $table->timestamps();

            $table->index('stock_opname_id');
            $table->index('outlet_material_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};