<?php

namespace App\Rules;

use App\Support\AudioDuration;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use SplFileInfo;

/**
 * Batas durasi berkas musik. Ukuran berkas tidak bisa menggantikan aturan ini:
 * 5 MB pada 32 kbps adalah dua puluh menit musik yang terus berputar di
 * belakang undangan.
 */
class AudioMaxDuration implements ValidationRule
{
    public function __construct(private readonly int $maxSeconds) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $path = match (true) {
            $value instanceof SplFileInfo => $value->getRealPath(),
            is_string($value) => $value,
            default => null,
        };

        if (! is_string($path) || $path === '') {
            return;
        }

        $seconds = AudioDuration::seconds($path);

        // Durasi yang tidak terbaca tidak dihitung sebagai pelanggaran. Ada
        // berkas MPEG sah yang headernya tidak bisa ditafsirkan, dan menolaknya
        // berarti menolak musik yang sebetulnya tidak apa-apa.
        if ($seconds === null || $seconds <= $this->maxSeconds) {
            return;
        }

        $fail(sprintf(
            'Durasi musik maksimal %s. Berkas ini %s.',
            self::humanize($this->maxSeconds),
            self::humanize($seconds),
        ));
    }

    private static function humanize(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $remainder = $seconds % 60;

        return match (true) {
            $minutes === 0 => "{$remainder} detik",
            $remainder === 0 => "{$minutes} menit",
            default => "{$minutes} menit {$remainder} detik",
        };
    }
}
