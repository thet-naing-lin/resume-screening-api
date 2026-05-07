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
        // Random experience level and years that make sense together
        $experienceLevel = $this->faker->randomElement(['junior', 'mid', 'senior']);
        $experienceYears = match ($experienceLevel) {
            'junior' => $this->faker->numberBetween(0, 2),
            'mid'    => $this->faker->numberBetween(2, 5),
            'senior' => $this->faker->numberBetween(5, 10),
        };

        // Some sample skills to pick from
        $skillsPool = [
            'PHP',
            'Laravel',
            'MySQL',
            'REST API',
            'Docker',
            'Git',
            'Unit Testing',
            'Redis',
            'JavaScript',
            'React',
        ];

        // Pick 3–6 random skills
        $requiredSkills = $this->faker->randomElements($skillsPool, $this->faker->numberBetween(3, 6));

        return [
            'user_id'               => User::factory(), // creator
            'title'                 => $this->faker->jobTitle(),
            'description'           => $this->faker->paragraphs(3, true),
            'required_skills'       => $requiredSkills, // will be JSON via cast
            'required_qualification' => $this->faker->randomElement([
                'Bachelor’s degree in Computer Science or related field',
                'Diploma in Software Engineering or equivalent experience',
                'Relevant IT certification with strong practical experience',
            ]),
            'experience_level'      => $experienceLevel, // junior / mid / senior
            'experience_years'      => $experienceYears,
            'employment_type'       => $this->faker->randomElement([
                'full-time',
                'part-time',
                'contract',
                'internship',
                'freelance',
            ]),
            'location'              => $this->faker->randomElement([
                'Yangon, Myanmar',
                'Mandalay, Myanmar',
                'Remote',
                'Singapore',
            ]),
            'status'                => $this->faker->randomElement(['active', 'closed']),
        ];
    }
}
