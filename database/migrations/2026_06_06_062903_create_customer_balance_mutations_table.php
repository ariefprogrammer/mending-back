<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_balance_mutations', function (Blueprint $table) {
            $table->id();

            $table->uuid('customer_id');
            $table->foreign('customer_id')
                ->references('id')
                ->on('customers')
                ->onDelete('cascade');

            $table->foreignId('outlet_id')
                ->constrained('outlets')
                ->onDelete('cascade');

            $table->enum('type', ['topup', 'deduction', 'refund', 'adjustment']);
            $table->decimal('amount', 12, 2);
            $table->decimal('balance_before', 12, 2);
            $table->decimal('balance_after', 12, 2);
            $table->text('notes')->nullable();

            $table->uuid('created_by_user_id')->nullable();
            $table->foreign('created_by_user_id')
                ->references('id')
                ->on('users')
                ->onDelete('set null');

            $table->uuid('created_by_employee_id')->nullable();
            $table->foreign('created_by_employee_id')
                ->references('id')
                ->on('employees')
                ->onDelete('set null');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_balance_mutations');
    }
};