<?php

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Nama tamu datang dari query string `?to=` dan diperlakukan sebagai input
 * tidak dipercaya. Nilai ini juga masuk ke OG title, jadi pembersihannya harus
 * terjadi sekali di sini — bukan tersebar di beberapa tempat pemakaian.
 */
final class GuestName
{
    public static function fromRequest(Request $request): string
    {
        return static::sanitize($request->query('to'));
    }

    public static function sanitize(mixed $value): string
    {
        if (! is_string($value)) {
            return (string) config('invitation.guest_name_fallback');
        }

        // `+` pada query string sudah menjadi spasi setelah PHP mem-parse-nya,
        // tetapi link yang di-share manual kadang memuat "%20" ganda atau tag.
        $clean = strip_tags($value);
        $clean = html_entity_decode($clean, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $clean = strip_tags($clean);
        // Buang karakter kontrol termasuk newline, lalu rapikan spasi berlebih.
        $clean = preg_replace('/[\p{C}]+/u', ' ', $clean) ?? '';
        $clean = trim(preg_replace('/\s+/u', ' ', $clean) ?? '');

        if ($clean === '') {
            return (string) config('invitation.guest_name_fallback');
        }

        return mb_substr($clean, 0, (int) config('invitation.guest_name_max_length'));
    }
}
