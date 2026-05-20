<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RoleAndSettingSeeder::class,
            ReferenceDataSeeder::class,
            UserSeeder::class,
            DemoCollectionSeeder::class,
        ]);
    }
}