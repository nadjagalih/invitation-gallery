<?php

namespace Database\Factories;

use App\Enums\Attendance;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\Rsvp>
 */
class RsvpFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'guest_id' => null,
            'name' => fake()->name(),
            'attendance' => fake()->randomElement(Attendance::cases()),
            'party_size' => fake()->numberBetween(1, 4),
            'message' => fake()->optional()->sentence(),
            'ip_hash' => hash('sha256', fake()->ipv4()),
            'user_agent' => fake()->userAgent(),
        ];
    }

    public function attending(): static
    {
        return $this->state(fn () => ['attendance' => Attendance::Attending]);
    }

    public function notAttending(): static
    {
        return $this->state(fn () => ['attendance' => Attendance::NotAttending]);
    }
}
