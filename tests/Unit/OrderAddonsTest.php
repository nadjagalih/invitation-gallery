<?php

namespace Tests\Unit;

use App\Enums\InvitationFeature;
use App\Support\OrderAddons;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * Jembatan dua arah antara kolom `orders.addons` dan repeater form. Yang diuji
 * di sini terutama arah sebaliknya: peta yang tersimpan menentukan hak fitur
 * pelanggan, jadi tidak boleh menerima kunci yang tidak dikenal.
 */
class OrderAddonsTest extends TestCase
{
    #[Test]
    public function peta_simpanan_menjadi_daftar_baris(): void
    {
        $rows = OrderAddons::toList([
            'music' => ['label' => 'Musik Latar', 'price' => 50000],
            'rsvpkit' => ['label' => 'RSVPKIT', 'price' => 75000],
        ]);

        $this->assertSame([
            ['feature' => 'music', 'price' => 50000],
            ['feature' => 'rsvpkit', 'price' => 75000],
        ], $rows);
    }

    #[Test]
    public function daftar_baris_menjadi_peta_simpanan(): void
    {
        $map = OrderAddons::toMap([
            ['feature' => 'music', 'price' => '50000'],
        ]);

        // Label ikut dibekukan dari enum, bukan dari kiriman form, supaya
        // perubahan daftar harga tidak mengubah isi order lama.
        $this->assertSame([
            'music' => ['label' => InvitationFeature::Music->label(), 'price' => 50000],
        ], $map);
    }

    #[Test]
    public function kolom_yang_masih_berupa_json_tetap_terbaca(): void
    {
        // Model men-cast `addons` menjadi array, tapi hook halaman bisa menerima
        // data mentah bila castnya belum berjalan.
        $rows = OrderAddons::toList('{"music":{"label":"Musik Latar","price":50000}}');

        $this->assertSame([['feature' => 'music', 'price' => 50000]], $rows);
    }

    #[Test]
    public function bentuk_yang_tidak_dikenal_menghasilkan_daftar_kosong(): void
    {
        $this->assertSame([], OrderAddons::toList(null));
        $this->assertSame([], OrderAddons::toList('bukan json'));
        $this->assertSame([], OrderAddons::toMap(null));
    }

    #[Test]
    public function fitur_di_luar_enum_dibuang(): void
    {
        // Kunci peta ini menjadi `invitations.features`, jadi fitur karangan dari
        // kiriman browser tidak boleh sampai tersimpan.
        $map = OrderAddons::toMap([
            ['feature' => 'music', 'price' => 50000],
            ['feature' => 'akses_admin', 'price' => 0],
            ['feature' => null, 'price' => 10000],
            'bukan baris',
        ]);

        $this->assertSame(['music'], array_keys($map));
    }

    #[Test]
    public function harga_negatif_dijepit_ke_nol(): void
    {
        $map = OrderAddons::toMap([['feature' => 'music', 'price' => -50000]]);

        $this->assertSame(0, $map['music']['price']);
    }

    #[Test]
    public function harga_bukan_angka_dianggap_nol(): void
    {
        $map = OrderAddons::toMap([['feature' => 'music', 'price' => 'gratis']]);

        $this->assertSame(0, $map['music']['price']);
    }

    #[Test]
    public function jumlah_dihitung_dari_daftar_baris(): void
    {
        $this->assertSame(125000, OrderAddons::sum([
            ['feature' => 'music', 'price' => 50000],
            ['feature' => 'rsvpkit', 'price' => 75000],
            ['feature' => 'karangan', 'price' => 999999],
        ]));

        $this->assertSame(0, OrderAddons::sum(null));
    }

    #[Test]
    public function baris_dengan_fitur_kembar_menyisakan_satu(): void
    {
        // Form memakai ->distinct(), tapi peta tetap harus punya satu kunci saja
        // bila validasi itu terlewat.
        $map = OrderAddons::toMap([
            ['feature' => 'music', 'price' => 50000],
            ['feature' => 'music', 'price' => 70000],
        ]);

        $this->assertCount(1, $map);
        $this->assertSame(70000, $map['music']['price']);
    }
}
