<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            CategorySeeder::class,
            ProductSeeder::class,
            // RawMaterialSeeder::class,  // add when you have a full materials list
            // AdminUserSeeder::class,    // add to seed your first CEO account
        ]);
    }
}
