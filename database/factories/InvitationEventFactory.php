<?php

namespace Database\Factories;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\InvitationEvent>
 */
class InvitationEventFactory extends Factory
{
    public function definition(): array
    {
        $startsAt = fake()->dateTimeBetween('+1 month', '+8 months');

        return [
            'invitation_id' => Invitation::factory(),
            'title' => 'Resepsi',
            'starts_at' => $startsAt,
            'ends_at' => (clone $startsAt)->modify('+3 hours'),
            'timezone' => 'Asia/Jakarta',
            'venue_name' => fake()->company().' Hall',
            'address' => fake()->streetAddress().', '.fake()->city(),
            'maps_url' => 'https://maps.google.com/?q='.fake()->latitude().','.fake()->longitude(),
            'notes' => null,
            'is_primary' => false,
            'sort_order' => 0,
        ];
    }

    public function akad(): static
    {
        return $this->state(fn () => [
            'title' => 'Akad Nikah',
            'is_primary' => true,
            'sort_order' => 0,
            'ends_at' => null,
        ]);
    }

    public function resepsi(): static
    {
        return $this->state(fn () => [
            'title' => 'Resepsi',
            'is_primary' => false,
            'sort_order' => 1,
        ]);
    }

    public function primary(): static
    {
        return $this->state(fn () => ['is_primary' => true]);
    }

    public function timezone(string $timezone): static
    {
        return $this->state(fn () => ['timezone' => $timezone]);
    }
}
