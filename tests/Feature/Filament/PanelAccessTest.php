<?php

namespace Tests\Feature\Filament;

use App\Models\Invitation;
use App\Models\Order;
use App\Models\Template;
use App\Models\Wish;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\PanelTestCase;

/**
 * Yang diuji di sini adalah pintunya, bukan isinya: apakah setiap halaman
 * benar-benar bisa dirender. Halaman Filament dibangun dari puluhan closure
 * yang tidak pernah dieksekusi sampai halamannya dibuka, jadi satu request per
 * halaman adalah pemeriksaan yang paling murah.
 */
class PanelAccessTest extends PanelTestCase
{
    /** @return array<string, array{string}> */
    public static function panelUrls(): array
    {
        return [
            'dashboard' => ['/admin'],
            'daftar undangan' => ['/admin/invitations'],
            'tambah undangan' => ['/admin/invitations/create'],
            'daftar template' => ['/admin/templates'],
            'tambah template' => ['/admin/templates/create'],
            'daftar kategori' => ['/admin/template-categories'],
            'tambah kategori' => ['/admin/template-categories/create'],
            'daftar order' => ['/admin/orders'],
            'tambah order' => ['/admin/orders/create'],
            'moderasi ucapan' => ['/admin/wishes'],
        ];
    }

    #[Test]
    #[DataProvider('panelUrls')]
    public function admin_bisa_membuka_setiap_halaman(string $url): void
    {
        $this->get($url)->assertOk();
    }

    #[Test]
    public function halaman_ubah_undangan_bisa_dibuka(): void
    {
        $invitation = Invitation::factory()
            ->for(Template::factory()->renderable())
            ->create();

        $this->get('/admin/invitations/'.$invitation->getKey().'/edit')->assertOk();
    }

    #[Test]
    public function halaman_ubah_order_bisa_dibuka(): void
    {
        $order = Order::factory()->for(Template::factory())->create();

        $this->get('/admin/orders/'.$order->getKey().'/edit')->assertOk();
    }

    #[Test]
    public function undangan_yang_dihapus_lunak_masih_bisa_dibuka(): void
    {
        $invitation = Invitation::factory()
            ->for(Template::factory()->renderable())
            ->create();

        $invitation->delete();

        // Tombol restore hanya berguna bila halamannya masih bisa dibuka.
        $this->get('/admin/invitations/'.$invitation->getKey().'/edit')->assertOk();
    }

    #[Test]
    public function ucapan_tidak_punya_halaman_tambah_maupun_ubah(): void
    {
        $wish = Wish::factory()
            ->for(Invitation::factory()->for(Template::factory()))
            ->create();

        $this->get('/admin/wishes/create')->assertNotFound();
        $this->get('/admin/wishes/'.$wish->getKey().'/edit')->assertNotFound();
    }
}
