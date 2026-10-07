<?php

namespace App\Support;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Validator;

/**
 * Perlindungan minimum untuk dua form terbuka tanpa autentikasi: honeypot dan
 * batas waktu submit minimum. Keduanya divalidasi di server — nilai apa pun
 * yang hanya diperiksa di browser tidak menahan bot sama sekali.
 *
 * Rate limit dan `ip_hash` ditangani terpisah; ini hanya lapisan bentuk isian.
 */
final class SpamGuard
{
    /** Dipanggil dari hook after() FormRequest. */
    public static function validate(Validator $validator): void
    {
        $input = $validator->getData();
        $honeypotField = (string) config('invitation.honeypot_field');
        $timestampField = (string) config('invitation.timestamp_field');

        // Manusia tidak melihat field ini, jadi terisi berarti bukan manusia.
        if (filled($input[$honeypotField] ?? null)) {
            $validator->errors()->add($honeypotField, 'Pengiriman tidak dapat diproses.');

            return;
        }

        $startedAt = self::decode($input[$timestampField] ?? null);

        if ($startedAt === null) {
            $validator->errors()->add($timestampField, 'Sesi form tidak dikenali. Muat ulang halaman lalu coba lagi.');

            return;
        }

        $age = now()->timestamp - $startedAt;

        if ($age < (int) config('invitation.min_submit_seconds')) {
            $validator->errors()->add($timestampField, 'Terlalu cepat. Mohon periksa isian Anda sekali lagi.');

            return;
        }

        if ($age > (int) config('invitation.max_form_age_seconds')) {
            $validator->errors()->add($timestampField, 'Halaman sudah lama terbuka. Muat ulang halaman lalu coba lagi.');
        }
    }

    /**
     * Nilai di form adalah timestamp terenkripsi, bukan angka biasa: kalau
     * angkanya polos, bot cukup mengirim waktu mana pun yang lolos ambang.
     */
    private static function decode(mixed $value): ?int
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        try {
            $decrypted = Crypt::decrypt($value);
        } catch (DecryptException) {
            return null;
        }

        return is_numeric($decrypted) ? (int) $decrypted : null;
    }
}
