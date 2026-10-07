<?php

namespace Tests\Feature;

use App\Enums\Attendance;
use App\Models\Invitation;
use App\Models\InvitationEvent;
use App\Models\Rsvp;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class RsvpSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function invitation(string $state = 'active'): Invitation
    {
        $invitation = Invitation::factory()
            ->for(Template::factory()->renderable())
            ->{$state}()
            ->create();

        InvitationEvent::factory()->akad()->for($invitation)->create();

        return $invitation->fresh();
    }

    /**
     * Payload valid. Timestamp form dibuat 30 detik lalu supaya lolos ambang
     * min_submit_seconds tanpa harus menahan test.
     *
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'form' => 'rsvp',
            'name' => 'Budi Santoso',
            'attendance' => Attendance::Attending->value,
            'party_size' => 2,
            'message' => 'Selamat menempuh hidup baru.',
            config('invitation.honeypot_field') => '',
            config('invitation.timestamp_field') => Crypt::encrypt(now()->subSeconds(30)->timestamp),
        ], $overrides);
    }

    #[Test]
    public function kiriman_valid_tersimpan_dan_menjawab_json_ok(): void
    {
        $invitation = $this->invitation();

        $this->postJson(route('invitation.rsvp', $invitation->slug), $this->payload())
            ->assertOk()
            ->assertJson(['ok' => true]);

        $rsvp = Rsvp::query()->sole();

        $this->assertSame($invitation->id, $rsvp->invitation_id);
        $this->assertSame('Budi Santoso', $rsvp->name);
        $this->assertSame(Attendance::Attending, $rsvp->attendance);
        $this->assertSame(2, $rsvp->party_size);

        // IP mentah tidak boleh tersimpan; hanya HMAC-nya.
        $this->assertNotNull($rsvp->ip_hash);
        $this->assertStringNotContainsString('127.0.0.1', (string) $rsvp->ip_hash);
    }

    #[Test]
    public function jumlah_tamu_kosong_dihitung_satu(): void
    {
        $invitation = $this->invitation();

        $this->postJson(route('invitation.rsvp', $invitation->slug), $this->payload(['party_size' => null]))
            ->assertOk();

        $this->assertSame(1, Rsvp::query()->sole()->party_size);
    }

    #[Test]
    public function honeypot_terisi_ditolak_dan_tidak_menyimpan_apa_pun(): void
    {
        $invitation = $this->invitation();

        $this->postJson(
            route('invitation.rsvp', $invitation->slug),
            $this->payload([config('invitation.honeypot_field') => 'http://spam.example'])
        )->assertStatus(422);

        $this->assertSame(0, Rsvp::query()->count());
    }

    #[Test]
    public function submit_terlalu_cepat_ditolak(): void
    {
        $invitation = $this->invitation();

        $this->postJson(
            route('invitation.rsvp', $invitation->slug),
            $this->payload([config('invitation.timestamp_field') => Crypt::encrypt(now()->timestamp)])
        )->assertStatus(422)->assertJsonValidationErrors(config('invitation.timestamp_field'));

        $this->assertSame(0, Rsvp::query()->count());
    }

    #[Test]
    public function timestamp_yang_bukan_hasil_enkripsi_kami_ditolak(): void
    {
        $invitation = $this->invitation();

        // Angka polos: bila diterima, bot cukup mengirim waktu mana pun.
        $this->postJson(
            route('invitation.rsvp', $invitation->slug),
            $this->payload([config('invitation.timestamp_field') => (string) now()->subHour()->timestamp])
        )->assertStatus(422)->assertJsonValidationErrors(config('invitation.timestamp_field'));
    }

    #[Test]
    public function form_yang_terlalu_lama_terbuka_ditolak(): void
    {
        $invitation = $this->invitation();

        $tooOld = now()->subSeconds((int) config('invitation.max_form_age_seconds') + 60)->timestamp;

        $this->postJson(
            route('invitation.rsvp', $invitation->slug),
            $this->payload([config('invitation.timestamp_field') => Crypt::encrypt($tooOld)])
        )->assertStatus(422)->assertJsonValidationErrors(config('invitation.timestamp_field'));
    }

    #[Test]
    public function isian_tidak_lengkap_menjawab_422(): void
    {
        $invitation = $this->invitation();

        $this->postJson(
            route('invitation.rsvp', $invitation->slug),
            $this->payload(['name' => 'A', 'attendance' => 'mungkin'])
        )->assertStatus(422)->assertJsonValidationErrors(['name', 'attendance']);
    }

    #[Test]
    public function jumlah_tamu_di_atas_batas_ditolak(): void
    {
        $invitation = $this->invitation();

        $overLimit = (int) config('invitation.rsvp_max_party_size') + 1;

        $this->postJson(
            route('invitation.rsvp', $invitation->slug),
            $this->payload(['party_size' => $overLimit])
        )->assertStatus(422)->assertJsonValidationErrors('party_size');
    }

    #[Test]
    public function halaman_demo_menolak_tulisan_tanpa_menyimpan(): void
    {
        $invitation = $this->invitation('preview');

        $this->postJson(route('invitation.rsvp', $invitation->slug), $this->payload())
            ->assertStatus(403)
            ->assertJson(['ok' => false]);

        $this->assertSame(0, Rsvp::query()->count());
    }

    #[Test]
    public function undangan_kedaluwarsa_menolak_tulisan(): void
    {
        $invitation = $this->invitation('expired');

        $this->postJson(route('invitation.rsvp', $invitation->slug), $this->payload())
            ->assertStatus(403);

        $this->assertSame(0, Rsvp::query()->count());
    }

    #[Test]
    public function slug_yang_tidak_ada_menjawab_404(): void
    {
        $this->postJson(route('invitation.rsvp', 'tidak-pernah-ada'), $this->payload())
            ->assertNotFound();
    }

    #[Test]
    public function submit_tanpa_javascript_kembali_dengan_flash_bernama(): void
    {
        $invitation = $this->invitation();

        $this->from(route('invitation.show', $invitation->slug))
            ->post(route('invitation.rsvp', $invitation->slug), $this->payload())
            ->assertRedirect(route('invitation.show', $invitation->slug))
            ->assertSessionHas('rsvp_success');

        $this->assertSame(1, Rsvp::query()->count());
    }

    #[Test]
    public function kegagalan_validasi_tanpa_javascript_memakai_error_bag_rsvp(): void
    {
        $invitation = $this->invitation();

        // Error bag terpisah supaya kegagalan RSVP tidak memerahi form ucapan
        // yang berada di halaman yang sama.
        $this->from(route('invitation.show', $invitation->slug))
            ->post(route('invitation.rsvp', $invitation->slug), $this->payload(['name' => '']))
            ->assertRedirect(route('invitation.show', $invitation->slug))
            ->assertSessionHasErrorsIn('rsvp', ['name']);
    }

    #[Test]
    public function batas_per_ip_per_menit_menjawab_429(): void
    {
        $invitation = $this->invitation();
        $limit = (int) config('invitation.rate_limits.per_ip_per_minute');

        for ($i = 0; $i < $limit; $i++) {
            $this->postJson(route('invitation.rsvp', $invitation->slug), $this->payload())->assertOk();
        }

        $this->postJson(route('invitation.rsvp', $invitation->slug), $this->payload())
            ->assertStatus(429);

        $this->assertSame($limit, Rsvp::query()->count());
    }
}
