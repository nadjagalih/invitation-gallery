<?php

namespace Tests\Feature;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\InvitationEvent;
use App\Models\Template;
use App\Models\Wish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvitationPageTest extends TestCase
{
    use RefreshDatabase;

    private function invitation(?string $state = 'active', array $attributes = []): Invitation
    {
        // renderable() memakai slug template yang benar-benar punya folder view.
        $factory = Invitation::factory()->for(Template::factory()->renderable());

        if ($state !== null) {
            $factory = $factory->{$state}();
        }

        $invitation = $factory->create($attributes);

        InvitationEvent::factory()->akad()->for($invitation)->create();
        InvitationEvent::factory()->resepsi()->for($invitation)->create();

        return $invitation->fresh();
    }

    #[Test]
    public function halaman_aktif_menampilkan_nama_tamu_dari_query_string(): void
    {
        $invitation = $this->invitation();

        $this->get(route('invitation.show', $invitation->slug).'?to=Budi+Santoso')
            ->assertOk()
            ->assertSee('Budi Santoso')
            ->assertSee($invitation->coupleNames());
    }

    #[Test]
    public function tanpa_query_string_nama_tamu_memakai_nilai_bawaan(): void
    {
        $invitation = $this->invitation();

        $this->get(route('invitation.show', $invitation->slug))
            ->assertOk()
            ->assertSee(config('invitation.guest_name_fallback'));
    }

    #[Test]
    public function nama_tamu_disanitasi_sebelum_dicetak(): void
    {
        $invitation = $this->invitation();

        $response = $this->get(route('invitation.show', $invitation->slug).'?to='.urlencode('<script>alert(1)</script>'));

        $response->assertOk();
        $this->assertStringNotContainsString('<script>alert(1)</script>', $response->getContent());
    }

    #[Test]
    public function meta_open_graph_dicetak_di_server(): void
    {
        $invitation = $this->invitation();

        $this->get(route('invitation.show', $invitation->slug))
            ->assertOk()
            ->assertSee('property="og:title"', false)
            ->assertSee('property="og:url"', false)
            // Undangan bersifat privat bagi penerima link.
            ->assertSee('noindex', false);
    }

    #[Test]
    public function status_preview_tetap_dirender_tetapi_form_tulis_dimatikan(): void
    {
        $invitation = $this->invitation('preview');

        $response = $this->get(route('invitation.show', $invitation->slug));

        $response->assertOk()->assertSee('halaman demo', false);
        $this->assertStringNotContainsString(
            route('invitation.rsvp', $invitation->slug),
            $response->getContent(),
        );
    }

    #[Test]
    public function draft_tidak_dapat_diakses_tanpa_signed_url(): void
    {
        $invitation = $this->invitation(null, ['status' => InvitationStatus::Draft]);

        $this->get(route('invitation.show', $invitation->slug))->assertNotFound();
    }

    #[Test]
    public function draft_dapat_diakses_lewat_signed_url_dan_nama_tamu_tetap_bebas(): void
    {
        $invitation = $this->invitation(null, ['status' => InvitationStatus::Draft]);

        $signed = URL::signedRoute('invitation.show', ['slug' => $invitation->slug]);

        $this->get($signed)->assertOk();

        // `to` sengaja dikecualikan dari tanda tangan supaya admin bisa menguji
        // beberapa nama tamu tanpa membuat link baru tiap kali.
        $this->get($signed.'&to=Rina')->assertOk()->assertSee('Rina');
    }

    #[Test]
    public function undangan_kedaluwarsa_menampilkan_halaman_perpanjangan(): void
    {
        $invitation = $this->invitation('expired');

        $this->get(route('invitation.show', $invitation->slug))
            ->assertOk()
            ->assertSee('wa.me/'.config('invitation.brand.whatsapp'), false);
    }

    #[Test]
    public function undangan_terarsip_menjawab_410(): void
    {
        $invitation = $this->invitation('archived');

        $this->get(route('invitation.show', $invitation->slug))->assertStatus(410);
    }

    #[Test]
    public function slug_yang_tidak_ada_menjawab_404(): void
    {
        $this->get(route('invitation.show', 'tidak-pernah-ada'))->assertNotFound();
    }

    #[Test]
    public function hanya_ucapan_yang_disetujui_yang_tampil(): void
    {
        $invitation = $this->invitation();

        Wish::factory()->for($invitation)->create(['name' => 'Disetujui', 'is_approved' => true]);
        Wish::factory()->for($invitation)->create(['name' => 'Menunggu', 'is_approved' => false]);

        $this->get(route('invitation.show', $invitation->slug))
            ->assertOk()
            ->assertSee('Disetujui')
            ->assertDontSee('Menunggu');
    }

    #[Test]
    public function tautan_halaman_ucapan_mempertahankan_nama_tamu(): void
    {
        config()->set('invitation.wishes_per_page', 2);

        $invitation = $this->invitation();
        Wish::factory()->count(5)->for($invitation)->create(['is_approved' => true]);

        $response = $this->get(route('invitation.show', $invitation->slug).'?to=Budi+Santoso');

        // Paginator memakai encoding RFC3986, jadi spasi menjadi %20.
        $response->assertOk()->assertSee('to=Budi%20Santoso', false);
    }

    #[Test]
    public function halaman_ucapan_kedua_membuka_undangan_tanpa_klik_ulang(): void
    {
        config()->set('invitation.wishes_per_page', 2);

        $invitation = $this->invitation();
        Wish::factory()->count(5)->for($invitation)->create(['is_approved' => true]);

        // `#main` harus sudah membawa kelas show, kalau tidak isi undangan
        // tersembunyi dan tamu harus menekan "Buka Undangan" lagi.
        $this->get(route('invitation.show', $invitation->slug).'?page=2')
            ->assertOk()
            ->assertSee('id="main" class="show"', false);
    }

    #[Test]
    public function template_tanpa_folder_versi_gagal_dengan_pesan_yang_menyebut_view(): void
    {
        $invitation = $this->invitation('active', ['template_version' => 99]);

        $this->withoutExceptionHandling();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/elegant-botanical\.v99/');

        $this->get(route('invitation.show', $invitation->slug));
    }
}
