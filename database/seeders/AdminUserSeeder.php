<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::firstOrCreate(
            ['email' => 'admin@resumescreening.com'],
            [
                'name'     => 'Super Admin',
                'password' => Hash::make('Admin@12345'),
            ]
        );

        $admin->assignRole('admin');

        $this->command->info('Admin user created: admin@resumescreening.com / Admin@12345');
    }
}
