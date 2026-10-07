<?php

namespace Tests\Unit;

use App\Support\InstagramProfile;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InstagramProfileTest extends TestCase
{
    #[Test]
    #[DataProvider('isianKlien')]
    public function berbagai_bentuk_isian_menghasilkan_satu_tautan(?string $input, ?string $expected): void
    {
        $this->assertSame($expected, InstagramProfile::handle($input));
    }

    public static function isianKlien(): array
    {
        return [
            'handle telanjang' => ['bagasprasetyo', 'bagasprasetyo'],
            'dengan at' => ['@bagasprasetyo', 'bagasprasetyo'],
            'url https' => ['https://instagram.com/bagasprasetyo', 'bagasprasetyo'],
            'url www' => ['https://www.instagram.com/bagasprasetyo/', 'bagasprasetyo'],
            'url dengan igsh' => ['https://www.instagram.com/bagasprasetyo?igsh=MXY5', 'bagasprasetyo'],
            'url dengan sub-path' => ['https://instagram.com/bagasprasetyo/reels/', 'bagasprasetyo'],
            'titik dan garis bawah dipertahankan' => ['ayu_lestari.id', 'ayu_lestari.id'],
            'spasi di tepi' => ['  bagasprasetyo  ', 'bagasprasetyo'],
            'kosong' => ['', null],
            'null' => [null, null],
            'hanya tanda baca' => ['@@@', null],
        ];
    }

    #[Test]
    public function url_tidak_pernah_menghasilkan_prefiks_ganda(): void
    {
        // Bug yang pernah terjadi: seeder menyimpan URL penuh sementara view
        // menambahkan host lagi, menghasilkan instagram.com/https://instagram...
        $this->assertSame(
            'https://instagram.com/bagasprasetyo',
            InstagramProfile::url('https://instagram.com/bagasprasetyo'),
        );
    }

    #[Test]
    public function label_selalu_diawali_at(): void
    {
        $this->assertSame('@ayulestari', InstagramProfile::label('https://instagram.com/ayulestari'));
        $this->assertNull(InstagramProfile::label(null));
    }
}
