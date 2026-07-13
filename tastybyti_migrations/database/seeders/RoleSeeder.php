<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('roles')->insert([
            ['name' => 'ceo',        'label' => 'CEO / Owner',   'created_at' => now(), 'updated_at' => now()],
            ['name' => 'supervisor', 'label' => 'Supervisor',    'created_at' => now(), 'updated_at' => now()],
            ['name' => 'cashier',    'label' => 'Cashier',       'created_at' => now(), 'updated_at' => now()],
            ['name' => 'cook',       'label' => 'Cook',          'created_at' => now(), 'updated_at' => now()],
            ['name' => 'admin',      'label' => 'System Admin',  'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
