<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_configurations', function (Blueprint $table) {
            $table->id(); // Primary Key (BigInt)
            
            // Foreign Key ke tabel outlets
            $table->unsignedBigInteger('outlet_id');
            
            // Boolean Fields
            $table->boolean('allow_multiple_services')->default(false);
            $table->boolean('allow_duplicate_service')->default(false);
            $table->boolean('input_total_pcs_mandatory')->default(false);
            $table->boolean('process_berurutan')->default(false);
            $table->boolean('payment_first')->default(false);
            $table->boolean('employee_update_data')->default(false);
            $table->boolean('is_tax_enabled')->default(false);
            
            // String & Numeric Fields
            $table->string('rounding_type')->nullable(); // varchar
            $table->integer('rounding_multiple')->default(0);
            $table->string('tax_type')->nullable(); // varchar
            $table->float('tax_percentage')->default(0);
            
            // Text Field
            $table->text('delivery_form_url')->nullable();
            
            $table->timestamps();

            // Definisi Foreign Key Constraint (Opsional, sesuaikan nama tabelnya)
            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_configurations');
    }
};
