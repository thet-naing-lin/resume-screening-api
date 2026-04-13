<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Create default admin account
        $admin = User::firstOrCreate(
            ['email' => 'admin@resume-tool.com'],
            [
                'name'     => 'Admin User',
                'password' => Hash::make('Admin@12345'),
            ]
        );

        // Assign admin role
        $adminRole = Role::where('name', 'admin')->first();

        if ($adminRole && !$admin->roles()->where('role_id', $adminRole->id)->exists()) {
            $admin->roles()->attach($adminRole->id);
        }

        // Create default HR account
        $hr = User::firstOrCreate(
            ['email' => 'hr@resume-tool.com'],
            [
                'name'     => 'HR User',
                'password' => Hash::make('Hr@12345'),
            ]
        );

        // Assign hr role
        $hrRole = Role::where('name', 'hr')->first();

        if ($hrRole && !$hr->roles()->where('role_id', $hrRole->id)->exists()) {
            $hr->roles()->attach($hrRole->id);
        }

        $this->command->info('✅ Default users seeded successfully.');
        $this->command->info('   Admin → admin@resume-tool.com / Admin@12345');
        $this->command->info('   HR    → hr@resume-tool.com / Hr@12345');
    }
}
