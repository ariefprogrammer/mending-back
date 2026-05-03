<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ServiceUnitSeeder extends Seeder
{
    public function run(): void
    {
        $serviceUnits = [
            ['name' => 'Kilogram', 'type' => 'berat'],
            ['name' => 'Gram',     'type' => 'berat'],
            ['name' => 'Pcs',      'type' => 'item'],
            ['name' => 'Lusin',    'type' => 'item'],
        ];

        DB::table('service_units')->insert($serviceUnits);
    }
}