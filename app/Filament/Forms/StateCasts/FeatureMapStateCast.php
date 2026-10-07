<?php

namespace App\Filament\Forms\StateCasts;

use App\Enums\InvitationFeature;
use Filament\Schemas\Components\StateCasts\Contracts\StateCast;
use Illuminate\Support\Arr;

/**
 * Menjembatani `invitations.features` dengan CheckboxList.
 *
 * Kolomnya berisi peta `{fitur: bool}` karena `hasFeature()` membacanya lewat
 * `data_get()`, sementara CheckboxList bekerja dengan daftar kunci yang
 * tercentang. Tanpa jembatan ini cast bawaan Filament akan mengubah
 * `['gallery' => true]` menjadi `['1']` — nilainya yang terbaca, kuncinya
 * hilang.
 *
 * Peta hasil dehidrasi selalu memuat seluruh fitur, termasuk yang bernilai
 * false. Kunci yang tidak dikenal tidak pernah ikut tertulis: petanya dibangun
 * dari enum, bukan dari kiriman browser.
 */
final class FeatureMapStateCast implements StateCast
{
    /** State checkbox menjadi nilai kolom. */
    public function get(mixed $state): array
    {
        $enabled = array_map(
            'strval',
            array_filter(Arr::wrap($state), 'is_scalar'),
        );

        return array_reduce(
            InvitationFeature::cases(),
            fn (array $carry, InvitationFeature $feature) => $carry + [
                $feature->value => in_array($feature->value, $enabled, true),
            ],
            [],
        );
    }

    /** Nilai kolom menjadi state checkbox. */
    public function set(mixed $state): array
    {
        if (is_string($state)) {
            $state = json_decode($state, associative: true);
        }

        if (! is_array($state)) {
            return [];
        }

        $known = InvitationFeature::values();
        $enabled = [];

        foreach ($state as $key => $value) {
            // Menerima dua bentuk: peta `{fitur: bool}` seperti isi kolom, dan
            // daftar `[fitur, ...]` seperti default yang ditulis di form.
            $feature = is_string($key) ? $key : (is_scalar($value) ? (string) $value : null);

            if ($feature === null || ! in_array($feature, $known, true)) {
                continue;
            }

            if (is_string($key) && ! $value) {
                continue;
            }

            $enabled[] = $feature;
        }

        return array_values(array_unique($enabled));
    }
}
