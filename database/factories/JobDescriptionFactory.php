<?php

namespace Database\Factories;

use App\Models\JobDescription;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobDescriptionFactory extends Factory
{
    protected $model = JobDescription::class;

    public function definition(): array
    {
        $titles = [
            'Full Stack Developer',
            'Backend Engineer',
            'Frontend Developer',
            'Data Analyst',
            'DevOps Engineer',
            'UI/UX Designer',
            'Product Manager',
            'HR Specialist',
        ];

        return [
            'title'            => fake()->jobTitle(),
            'description'      => fake()->paragraphs(3, true),
            'user_id'          => User::factory(),  // ← change created_by to user_id
            'required_skills'  => 'PHP, Laravel, React',
            'experience_level' => fake()->randomElement(['junior', 'mid', 'senior']),
            'employment_type'  => fake()->randomElement(['full-time', 'part-time', 'contract']),
        ];
    }
}
