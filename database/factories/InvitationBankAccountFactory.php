<?php

namespace Database\Factories;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\InvitationBankAccount>
 */
class InvitationBankAccountFactory extends Factory
{
    public function definition(): array
    {
        return [
            'invitation_id' => Invitation::factory(),
            'bank_name' => fake()->randomElement(['Bank BCA', 'Bank Mandiri', 'Bank BNI', 'Bank BRI']),
            // Nol di depan harus utuh, karena itu string.
            'account_number' => '0'.fake()->numerify('#########'),
            'account_holder' => fake()->name(),
            'logo_media_id' => null,
            'sort_order' => 0,
        ];
    }
}
