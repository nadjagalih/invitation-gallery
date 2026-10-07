<?php

namespace Tests\Unit;

use App\Support\GuestName;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * `?to=` adalah satu-satunya input tak dipercaya pada halaman undangan, dan
 * nilainya ikut masuk ke OG title. Pembersihannya diuji terpisah dari
 * controller supaya tetap terjaga saat halaman berubah.
 */
class GuestNameTest extends TestCase
{
    #[Test]
    #[DataProvider('nilaiYangDibersihkan')]
    public function nama_tamu_dibersihkan(mixed $input, string $expected): void
    {
        $this->assertSame($expected, GuestName::sanitize($input));
    }

    public static function nilaiYangDibersihkan(): array
    {
        $fallback = 'Tamu Undangan';

        return [
            'nama biasa' => ['Budi Santoso', 'Budi Santoso'],
            'spasi berlebih dirapikan' => ['  Budi   Santoso  ', 'Budi Santoso'],
            'tag dibuang' => ['<b>Budi</b>', 'Budi'],
            'script dibuang seluruhnya' => ['<script>alert(1)</script>', 'alert(1)'],
            'entitas ganda tetap dibersihkan' => ['&lt;script&gt;x&lt;/script&gt;', 'x'],
            'baris baru menjadi spasi' => ["Budi\nSantoso", 'Budi Santoso'],
            'string kosong memakai fallback' => ['', $fallback],
            'hanya spasi memakai fallback' => ['   ', $fallback],
            'null memakai fallback' => [null, $fallback],
            'array memakai fallback' => [['Budi'], $fallback],
        ];
    }

    #[Test]
    public function nama_terlalu_panjang_dipotong_pada_batas_config(): void
    {
        config()->set('invitation.guest_name_max_length', 10);

        $this->assertSame('Budi Santo', GuestName::sanitize('Budi Santoso Wijaya'));
    }

    #[Test]
    public function pemotongan_aman_untuk_karakter_multibyte(): void
    {
        config()->set('invitation.guest_name_max_length', 3);

        // mb_substr, bukan substr: memotong di tengah byte UTF-8 menghasilkan
        // karakter rusak yang muncul di judul link WhatsApp.
        $this->assertSame('Ayū', GuestName::sanitize('Ayūmi Lestari'));
    }
}
