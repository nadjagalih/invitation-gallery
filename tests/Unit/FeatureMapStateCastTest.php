<?php

namespace Tests\Unit;

use App\Enums\InvitationFeature;
use App\Filament\Forms\StateCasts\FeatureMapStateCast;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * `invitations.features` berbentuk peta karena hasFeature() membacanya lewat
 * data_get(), sedangkan CheckboxList bekerja dengan daftar kunci tercentang.
 * Cast ini jembatannya, dan yang penting dijaga: kuncinya tidak boleh hilang.
 */
class FeatureMapStateCastTest extends TestCase
{
    private FeatureMapStateCast $cast;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cast = new FeatureMapStateCast;
    }

    #[Test]
    public function peta_kolom_menjadi_daftar_yang_tercentang(): void
    {
        $state = $this->cast->set([
            'gallery' => true,
            'music' => false,
            'rsvp' => true,
        ]);

        $this->assertSame(['gallery', 'rsvp'], $state);
    }

    #[Test]
    public function default_form_yang_sudah_berbentuk_daftar_diterima_apa_adanya(): void
    {
        // Nilai default ditulis sebagai daftar kunci, bukan peta.
        $this->assertSame(['gallery', 'rsvp'], $this->cast->set(['gallery', 'rsvp']));
    }

    #[Test]
    public function kolom_yang_masih_berupa_json_tetap_terbaca(): void
    {
        $this->assertSame(['gallery'], $this->cast->set('{"gallery":true,"music":false}'));
    }

    #[Test]
    public function bentuk_yang_tidak_dikenal_menghasilkan_daftar_kosong(): void
    {
        $this->assertSame([], $this->cast->set(null));
        $this->assertSame([], $this->cast->set('bukan json'));
    }

    #[Test]
    public function fitur_di_luar_enum_dibuang_saat_dibaca(): void
    {
        $this->assertSame(['gallery'], $this->cast->set([
            'gallery' => true,
            'akses_admin' => true,
        ]));
    }

    #[Test]
    public function daftar_tercentang_menjadi_peta_lengkap(): void
    {
        $map = $this->cast->get(['gallery', 'rsvp']);

        // Peta hasil dehidrasi memuat seluruh fitur, termasuk yang false —
        // dibangun dari enum, bukan dari kiriman browser.
        $this->assertSame(InvitationFeature::values(), array_keys($map));
        $this->assertTrue($map['gallery']);
        $this->assertTrue($map['rsvp']);
        $this->assertFalse($map['music']);
    }

    #[Test]
    public function fitur_karangan_tidak_pernah_ikut_tertulis(): void
    {
        $map = $this->cast->get(['gallery', 'akses_admin']);

        $this->assertArrayNotHasKey('akses_admin', $map);
    }

    #[Test]
    public function tanpa_centang_seluruh_fitur_menjadi_false(): void
    {
        $map = $this->cast->get(null);

        $this->assertSame(InvitationFeature::values(), array_keys($map));
        $this->assertEmpty(array_filter($map));
    }

    #[Test]
    public function bolak_balik_mempertahankan_pilihan(): void
    {
        $original = InvitationFeature::defaults();

        $this->assertSame($original, $this->cast->get($this->cast->set($original)));
    }
}
