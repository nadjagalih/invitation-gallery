<?php

namespace Database\Factories;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\InvitationStory>
 */
class InvitationStoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'title' => 'Awal Bertemu',
            'happened_on' => fake()->dateTimeBetween('-8 years', '-2 years'),
            'body' => fake()->paragraph(3),
            'media_id' => null,
            'sort_order' => 0,
        ];
    }
}
