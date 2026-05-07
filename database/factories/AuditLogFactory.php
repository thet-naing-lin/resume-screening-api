<?php

namespace Database\Factories;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    public function definition(): array
    {
        $actions = [
            'auth.login',
            'auth.logout',
            'resume.uploaded',
            'resume.deleted',
            'candidate.email_sent',
            'rankings.exported',
            'user.created',
            'user.role_assigned',
            'status.updated',
        ];

        return [
            'user_id'    => User::factory(),
            'action'     => fake()->randomElement($actions),
            'model_type' => 'App\\Models\\Resume',
            'model_id'   => fake()->numberBetween(1, 100),
            'metadata'   => json_encode([
                'ip' => fake()->ipv4(),
            ]),
            'created_at' => fake()->dateTimeBetween('-30 days', 'now'),
        ];
    }

    public function forAction(string $action): static
    {
        return $this->state(fn() => ['action' => $action]);
    }
}
