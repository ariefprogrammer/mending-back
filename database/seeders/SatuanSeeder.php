<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SatuanSeeder extends Seeder
{
    public function run(): void
    {
        $satuans = [
            // Satuan Berat
            ['name' => 'Kg', 'type' => 'berat', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Gram', 'type' => 'berat', 'created_at' => now(), 'updated_at' => now()],

            // Satuan Item
            ['name' => 'Pcs',       'type' => 'item', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Lusin',     'type' => 'item', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Pasang',    'type' => 'item', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'Sett',       'type' => 'item', 'created_at' => now(), 'updated_at' => now()],
        ];

        DB::table('satuans')->insert($satuans);
    }
}