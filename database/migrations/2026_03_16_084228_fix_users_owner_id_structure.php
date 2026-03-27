<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // 1. Hapus owner_code jika ada
            if (Schema::hasColumn('users', 'owner_code')) {
                $table->dropColumn('owner_code');
            }

            // 2. Bersihkan owner_id lama jika ada (termasuk foreign key-nya)
            if (Schema::hasColumn('users', 'owner_id')) {
                try {
                    // Coba hapus foreign key constraint secara manual
                    $table->dropForeign(['owner_id']);
                } catch (\Exception $e) {
                    // Abaikan jika memang tidak ada foreign key
                }
                $table->dropColumn('owner_id');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            // 3. Buat ulang owner_id sebagai kolom mandiri, unik, dan tidak berelasi
            $table->unsignedBigInteger('owner_id')->unique()->nullable()->after('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('owner_id');
        });
    }
};