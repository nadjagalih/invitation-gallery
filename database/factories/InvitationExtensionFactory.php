<?php

namespace Database\Factories;

use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<\App\Models\InvitationExtension>
 */
class InvitationExtensionFactory extends Factory
{
    public function definition(): array
    {
        $previous = now()->subDays(3);
        $days = 90;

        return [
            'invitation_id' => Invitation::factory(),
            'order_id' => null,
            'previous_expires_at' => $previous,
            'new_expires_at' => $previous->copy()->addDays($days),
            'days' => $days,
        ];
    }
}
