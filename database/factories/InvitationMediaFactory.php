<?php

namespace Database\Factories;

use App\Enums\MediaCollection;
use App\Models\Invitation;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<\App\Models\InvitationMedia>
 */
class InvitationMediaFactory extends Factory
{
    public function definition(): array
    {
        $hash = Str::random(32);

        return [
            'invitation_id' => Invitation::factory(),
            'collection' => MediaCollection::Gallery,
            'disk' => 'local',
            'path' => "invitations/1/gallery/{$hash}.webp",
            'mime' => 'image/webp',
            'size' => fake()->numberBetween(80_000, 900_000),
            'width' => 1440,
            'height' => 1440,
            'duration_seconds' => null,
            'variants' => null,
            'sort_order' => 0,
        ];
    }

    public function collection(MediaCollection $collection): static
    {
        return $this->state(function (array $attributes) use ($collection) {
            $hash = Str::random(32);
            $extension = $collection === MediaCollection::Music ? 'mp3' : 'webp';
            $directory = $collection->directory();

            return [
                'collection' => $collection,
                'path' => "invitations/{$attributes['invitation_id']}/{$directory}/{$hash}.{$extension}",
                'mime' => $collection === MediaCollection::Music ? 'audio/mpeg' : 'image/webp',
                'width' => $collection === MediaCollection::Music ? null : 1440,
                'height' => $collection === MediaCollection::Music ? null : 1440,
                'duration_seconds' => $collection === MediaCollection::Music ? 180 : null,
            ];
        });
    }

    /** Media yang sudah dilewati job konversi: derivative terisi. */
    public function processed(): static
    {
        return $this->state(function (array $attributes) {
            $base = Str::beforeLast($attributes['path'], '.');

            return [
                'variants' => [
                    'original' => $attributes['path'],
                    '480' => "{$base}-480.webp",
                    '960' => "{$base}-960.webp",
                    '1440' => "{$base}-1440.webp",
                ],
            ];
        });
    }

    public function onDisk(string $disk): static
    {
        return $this->state(fn () => ['disk' => $disk]);
    }
}
