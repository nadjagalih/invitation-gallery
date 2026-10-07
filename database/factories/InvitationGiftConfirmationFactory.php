<?php

namespace Database\Factories;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\InvitationGiftConfirmation>
 */
class InvitationGiftConfirmationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'sender_name' => fake()->name(),
            'amount' => fake()->numberBetween(50, 1000) * 1000,
            'invitation_bank_account_id' => null,
            'note' => fake()->sentence(),
            'proof_path' => null,
            'confirmed_at' => null,
        ];
    }

    public function confirmed(): static
    {
        return $this->state(fn () => ['confirmed_at' => now()]);
    }
}
