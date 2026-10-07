<?php

namespace Database\Factories;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Wish>
 */
class WishFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'guest_id' => null,
            'name' => fake()->name(),
            'message' => fake()->sentence(12),
            'is_approved' => true,
            'ip_hash' => hash('sha256', fake()->ipv4()),
        ];
    }

    public function pending(): static
    {
        return $this->state(fn () => ['is_approved' => false]);
    }
}
