<?php

namespace Database\Factories;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Guest>
 */
class GuestFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'token' => Str::random(24),
            'group_label' => null,
            'quota' => 1,
            'opened_at' => null,
        ];
    }
}
