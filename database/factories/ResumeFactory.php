<?php

namespace Database\Factories;

use App\Models\Candidate;
use App\Models\JobDescription;
use App\Models\Resume;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ResumeFactory extends Factory
{
    protected $model = Resume::class;

    public function definition(): array
    {
        $filename = fake()->firstName() . '_' . fake()->lastName() . '.pdf';

        return [
            'job_description_id' => JobDescription::factory(),
            'uploaded_by'        => User::factory(),
            'candidate_id'       => Candidate::factory(),
            'original_filename'  => $filename,
            'stored_filename'    => 'resumes/' . $filename,
            'file_type'          => 'pdf',
            'file_size'          => fake()->numberBetween(100000, 4000000), // 100KB - 4MB
            'status'             => 'scored',
            'parse_error'        => null,
        ];
    }

    // Uploaded but not yet processed
    public function uploaded(): static
    {
        return $this->state(fn() => ['status' => 'uploaded']);
    }

    // Failed parsing
    public function failed(): static
    {
        return $this->state(fn() => [
            'status'      => 'failed',
            'parse_error' => 'Could not extract text from file.',
        ]);
    }

    // DOCX file
    public function docx(): static
    {
        return $this->state(function () {
            $filename = fake()->firstName() . '_' . fake()->lastName() . '.docx';
            return [
                'original_filename' => $filename,
                'stored_filename'   => 'resumes/' . $filename,
                'file_type'         => 'docx',
            ];
        });
    }
}
