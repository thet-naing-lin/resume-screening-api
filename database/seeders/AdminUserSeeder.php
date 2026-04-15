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

        $xin = User::firstOrCreate(
            ['email' => 'xin@gmail.com'],
            [
                'name'     => 'xin',
                'password' => Hash::make('asdfasdf'),
            ]
        );

        $xin->assignRole('admin');

        $hr = User::firstOrCreate(
            ['email' => 'hr@resumescreening.com'],
            [
                'name'     => 'HR User',
                'password' => Hash::make('asdfasdf'),
            ]
        );

        $hr->assignRole('hr');

        $this->command->info('Admin user created: admin@resumescreening.com / Admin@12345');
    }
}
