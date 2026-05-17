<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->foreignId('outlet_material_category_id')->nullable()->constrained('outlet_material_categories')->nullOnDelete();
            $table->string('name', 100);
            $table->foreignId('satuan_id')->nullable()->constrained('satuans')->nullOnDelete();
            $table->bigInteger('min_stock_alert')->default(0);
            $table->bigInteger('current_quantity')->default(0);
            $table->timestamps();

            $table->index('outlet_id');
            $table->index('outlet_material_category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_materials');
    }
};