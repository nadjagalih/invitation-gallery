<?php

namespace App\Support;

use App\Enums\InvitationFeature;

/**
 * Menerjemahkan `orders.addons` antara bentuk simpanan dan bentuk form.
 *
 * Kolomnya berbentuk peta `{fitur: {label, price}}` karena
 * `Order::purchasedFeatures()` membaca kuncinya untuk menurunkan
 * `invitations.features`. Repeater Filament sebaliknya bekerja dengan daftar
 * baris. Penerjemahan tidak dititipkan ke StateCast: cast dijalankan sebelum
 * `Repeater::dehydrateItems()`, yang menutup dengan `array_values()` dan akan
 * membuang kunci petanya. Jadi ia dikerjakan di hook halaman, dua titik yang
 * memang disediakan untuk ini.
 *
 * Harga dan label ikut disimpan apa adanya supaya perubahan daftar harga di
 * kemudian hari tidak mengubah isi order lama.
 */
final class OrderAddons
{
    public const FEATURE = 'feature';

    public const PRICE = 'price';

    /**
     * Peta simpanan menjadi daftar baris repeater.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function toList(mixed $map): array
    {
        if (is_string($map)) {
            $map = json_decode($map, associative: true);
        }

        if (! is_array($map)) {
            return [];
        }

        $rows = [];

        foreach ($map as $feature => $addon) {
            // Bentuk lama atau kiriman tak terduga bisa berupa angka polos.
            $price = is_array($addon) ? ($addon[self::PRICE] ?? 0) : $addon;

            $rows[] = [
                self::FEATURE => (string) $feature,
                self::PRICE => is_numeric($price) ? (int) $price : 0,
            ];
        }

        return $rows;
    }

    /**
     * Daftar baris repeater menjadi peta simpanan. Baris tanpa fitur dan fitur
     * di luar enum dibuang — kunci peta ini menjadi hak fitur pelanggan, jadi
     * tidak boleh berisi apa pun yang tidak dikenal.
     *
     * @return array<string, array<string, mixed>>
     */
    public static function toMap(mixed $rows): array
    {
        if (! is_array($rows)) {
            return [];
        }

        $map = [];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }

            $feature = $row[self::FEATURE] ?? null;

            if (! is_string($feature) || ! in_array($feature, InvitationFeature::values(), true)) {
                continue;
            }

            $price = $row[self::PRICE] ?? 0;

            $map[$feature] = [
                'label' => InvitationFeature::from($feature)->label(),
                self::PRICE => is_numeric($price) ? max(0, (int) $price) : 0,
            ];
        }

        return $map;
    }

    /** Jumlah harga add-on dari daftar baris, sebelum tersimpan sebagai peta. */
    public static function sum(mixed $rows): int
    {
        return array_sum(array_column(self::toMap($rows), self::PRICE));
    }
}
