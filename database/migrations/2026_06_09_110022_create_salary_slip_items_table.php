<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('salary_slip_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('salary_slip_id')
                  ->constrained('salary_slips')
                  ->onDelete('cascade');

            $table->foreignId('salary_component_id')
                  ->constrained('salary_components')
                  ->onDelete('cascade');

            $table->bigInteger('amount')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('salary_slip_items');
    }
};