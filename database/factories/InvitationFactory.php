<?php

namespace Database\Factories;

use App\Enums\AssetState;
use App\Enums\InvitationFeature;
use App\Enums\InvitationStatus;
use App\Models\Template;
use App\Support\InvitationLifecycle;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\Invitation>
 */
class InvitationFactory extends Factory
{
    public function definition(): array
    {
        $groom = fake()->firstNameMale();
        $bride = fake()->firstNameFemale();
        $eventDate = fake()->dateTimeBetween('+1 month', '+8 months');

        return [
            'user_id' => null,
            'template_id' => Template::factory(),
            'template_version' => 1,
            'slug' => Str::slug("{$groom}-{$bride}").'-'.fake()->unique()->numberBetween(1000, 9999),
            'slug_locked_at' => null,
            'status' => InvitationStatus::Draft,
            'asset_state' => AssetState::Retained,
            // Factory keeps local as a legacy-disk fixture; production defaults
            // are controlled by the migration/configuration.
            'asset_disk' => 'local',
            'features' => InvitationFeature::defaults(),

            'groom_nickname' => $groom,
            'groom_full_name' => $groom.' '.fake()->lastName(),
            'groom_child_order' => 'Pertama',
            'groom_father' => 'Bapak '.fake()->firstNameMale().' '.fake()->lastName(),
            'groom_mother' => 'Ibu '.fake()->firstNameFemale().' '.fake()->lastName(),
            'groom_instagram' => 'https://instagram.com/'.Str::lower($groom),

            'bride_nickname' => $bride,
            'bride_full_name' => $bride.' '.fake()->lastName(),
            'bride_child_order' => 'Kedua',
            'bride_father' => 'Bapak '.fake()->firstNameMale().' '.fake()->lastName(),
            'bride_mother' => 'Ibu '.fake()->firstNameFemale().' '.fake()->lastName(),
            'bride_instagram' => 'https://instagram.com/'.Str::lower($bride),

            'event_date' => $eventDate,
            'quote_text' => 'Dan di antara tanda-tanda kekuasaan-Nya ialah Dia menciptakan untukmu pasangan dari jenismu sendiri, agar kamu merasa tenteram kepadanya.',
            'quote_source' => 'QS. Ar-Rum: 21',
            'opening_words' => 'Dengan memohon rahmat dan ridho Allah Subhanahu Wa Ta\'ala, kami bermaksud menyelenggarakan pernikahan putra-putri kami.',
            'closing_words' => 'Merupakan suatu kebahagiaan dan kehormatan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu.',
            'gift_address' => fake()->streetAddress().', '.fake()->city(),
            'moderate_wishes' => true,

            'published_at' => null,
            'expires_at' => null,
            'grace_days' => config('invitation.grace_days'),
            'asset_delete_at' => null,
            'expiry_notified_at' => null,
            'meta' => null,
        ];
    }

    public function preview(): static
    {
        return $this->state(fn () => [
            'status' => InvitationStatus::Preview,
            'slug' => 'demo-'.Str::slug(fake()->unique()->words(2, true)),
        ]);
    }

    /** Undangan aktif dengan expires_at dan asset_delete_at yang konsisten. */
    public function active(): static
    {
        return $this->state(function (array $attributes) {
            $eventDate = \Illuminate\Support\Carbon::parse($attributes['event_date']);
            $expiresAt = InvitationLifecycle::expiryFor($eventDate);

            return [
                'status' => InvitationStatus::Active,
                'published_at' => now(),
                'slug_locked_at' => now(),
                'expires_at' => $expiresAt,
                'asset_delete_at' => InvitationLifecycle::assetDeleteFor($expiresAt, $attributes['grace_days']),
            ];
        });
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'status' => InvitationStatus::Expired,
            'published_at' => now()->subMonths(6),
            'slug_locked_at' => now()->subMonths(6),
            'event_date' => now()->subMonths(4),
            'expires_at' => now()->subDay(),
            'asset_delete_at' => now()->addDays(config('invitation.grace_days')),
        ]);
    }

    public function pendingDeletion(): static
    {
        return $this->expired()->state(fn () => [
            'asset_state' => AssetState::DeletionPending,
            'asset_delete_at' => now()->subHour(),
        ]);
    }

    public function archived(): static
    {
        return $this->expired()->state(fn () => [
            'status' => InvitationStatus::Archived,
            'asset_state' => AssetState::Deleted,
            'asset_delete_at' => now()->subDays(2),
        ]);
    }

    public function forDisk(string $disk): static
    {
        return $this->state(fn () => ['asset_disk' => $disk]);
    }
}
