<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            [
                'name'        => 'admin',
                'description' => 'Full system access — manage users and roles',
            ],
            [
                'name'        => 'hr',
                'description' => 'HR recruiter — manage jobs, upload resumes, view candidates',
            ],
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(
                ['name' => $role['name']],
                ['description' => $role['description']]
            );
        }

        $this->command->info('✅ Roles seeded successfully.');
    }
}
