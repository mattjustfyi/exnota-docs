<?php

declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\WebhookEvent>
 */
class WebhookEventFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'url' => $this->faker->url(),
            'headers' => [
                'X-GitHub-Event' => $this->faker->randomElement(['push', 'pull_request', 'issues']),
                'X-GitHub-Delivery' => $this->faker->uuid(),
                'User-Agent' => 'GitHub-Hookshot/123abc',
                'Content-Type' => 'application/json',
            ],
            'body' => [
                'action' => $this->faker->randomElement(['opened', 'closed', 'synchronized']),
                'repository' => [
                    'full_name' => 'owner/repository',
                    'id' => $this->faker->randomNumber(),
                ],
                'sender' => [
                    'login' => $this->faker->userName(),
                    'id' => $this->faker->randomNumber(),
                ],
            ],
        ];
    }
}
