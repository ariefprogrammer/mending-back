<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            // Drop foreign key lama
            $table->dropForeign('services_satuan_id_foreign');
            
            // Tambah foreign key baru ke satuans
            $table->foreign('satuan_id')
                ->references('id')
                ->on('satuans')
                ->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropForeign('services_satuan_id_foreign');
            
            $table->foreign('satuan_id')
                ->references('id')
                ->on('units')
                ->onDelete('set null');
        });
    }
};
