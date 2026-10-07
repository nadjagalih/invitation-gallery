<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Klien mengisi kolom Instagram dengan apa saja: `@nama`, `nama`, atau tempelan
 * URL penuh dari address bar lengkap dengan query `?igsh=...`. Ketiganya harus
 * menghasilkan satu tautan yang benar, bukan `instagram.com/https://...`.
 */
final class InstagramProfile
{
    public static function handle(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        // Buang skema dan host bila yang ditempel adalah URL penuh.
        if (Str::contains($value, 'instagram.com')) {
            $value = (string) Str::after($value, 'instagram.com');
        }

        $value = Str::before($value, '?');
        $value = Str::before($value, '#');
        $value = trim($value, "/@ \t\n\r\0\x0B");

        // Sisakan segmen pertama: `nama/reels` bukan username.
        $value = Str::before($value, '/');

        // Username Instagram hanya huruf, angka, titik, dan garis bawah.
        $value = preg_replace('/[^A-Za-z0-9._]/', '', $value) ?? '';

        return $value === '' ? null : $value;
    }

    public static function url(?string $value): ?string
    {
        $handle = self::handle($value);

        return $handle ? "https://instagram.com/{$handle}" : null;
    }

    public static function label(?string $value): ?string
    {
        $handle = self::handle($value);

        return $handle ? "@{$handle}" : null;
    }
}
