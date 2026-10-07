<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\Template;
use App\Models\Wish;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WishSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function invitation(string $state = 'active', array $attributes = []): Invitation
    {
        return Invitation::factory()
            ->for(Template::factory()->renderable())
            ->{$state}()
            ->create($attributes);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'form' => 'wishes',
            'name' => 'Siti Aminah',
            'message' => 'Barakallahu lakuma wa baraka alaika.',
            config('invitation.honeypot_field') => '',
            config('invitation.timestamp_field') => Crypt::encrypt(now()->subSeconds(30)->timestamp),
        ], $overrides);
    }

    #[Test]
    public function undangan_bermoderasi_menyimpan_ucapan_sebagai_belum_disetujui(): void
    {
        $invitation = $this->invitation('active', ['moderate_wishes' => true]);

        $response = $this->postJson(route('invitation.wishes', $invitation->slug), $this->payload());

        $response->assertOk()
            ->assertJson(['ok' => true])
            // Ucapan yang menunggu moderasi tidak boleh dikirim balik ke
            // browser: kalau dikirim, ia langsung tampil di daftar publik.
            ->assertJson(['wish' => null]);

        $wish = Wish::query()->sole();
        $this->assertFalse($wish->is_approved);
    }

    #[Test]
    public function tanpa_moderasi_ucapan_langsung_disetujui_dan_dikirim_balik(): void
    {
        $invitation = $this->invitation('active', ['moderate_wishes' => false]);

        $response = $this->postJson(route('invitation.wishes', $invitation->slug), $this->payload());

        $response->assertOk()->assertJsonPath('wish.name', 'Siti Aminah');

        $this->assertTrue(Wish::query()->sole()->is_approved);
    }

    #[Test]
    public function ucapan_kosong_menjawab_422(): void
    {
        $invitation = $this->invitation();

        $this->postJson(route('invitation.wishes', $invitation->slug), $this->payload(['message' => '']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('message');

        $this->assertSame(0, Wish::query()->count());
    }

    #[Test]
    public function honeypot_terisi_ditolak(): void
    {
        $invitation = $this->invitation();

        $this->postJson(
            route('invitation.wishes', $invitation->slug),
            $this->payload([config('invitation.honeypot_field') => 'x'])
        )->assertStatus(422);

        $this->assertSame(0, Wish::query()->count());
    }

    #[Test]
    public function halaman_demo_menolak_ucapan(): void
    {
        $invitation = $this->invitation('preview');

        $this->postJson(route('invitation.wishes', $invitation->slug), $this->payload())
            ->assertStatus(403)
            ->assertJson(['ok' => false]);

        $this->assertSame(0, Wish::query()->count());
    }

    #[Test]
    public function kegagalan_validasi_tanpa_javascript_memakai_error_bag_wishes(): void
    {
        $invitation = $this->invitation();

        $this->from(route('invitation.show', $invitation->slug))
            ->post(route('invitation.wishes', $invitation->slug), $this->payload(['message' => '']))
            ->assertRedirect(route('invitation.show', $invitation->slug))
            ->assertSessionHasErrorsIn('wishes', ['message']);
    }

    #[Test]
    public function submit_tanpa_javascript_kembali_dengan_flash_bernama(): void
    {
        $invitation = $this->invitation();

        $this->from(route('invitation.show', $invitation->slug))
            ->post(route('invitation.wishes', $invitation->slug), $this->payload())
            ->assertRedirect(route('invitation.show', $invitation->slug))
            ->assertSessionHas('wishes_success');
    }
}
