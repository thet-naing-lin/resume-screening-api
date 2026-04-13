<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters — roles must exist before users
        $this->call([
            RoleSeeder::class,
            UserSeeder::class,
        ]);
    }
}
