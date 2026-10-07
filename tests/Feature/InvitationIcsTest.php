<?php

namespace Tests\Feature;

use App\Models\Invitation;
use App\Models\InvitationEvent;
use App\Models\Template;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * File .ics dibuat di server, bukan dari Blob di browser: iOS Safari menolak
 * sebagian unduhan Blob, dan itu justru perangkat mayoritas tamu.
 */
class InvitationIcsTest extends TestCase
{
    use RefreshDatabase;

    private function invitation(string $state = 'active'): Invitation
    {
        return Invitation::factory()
            ->for(Template::factory()->renderable())
            ->{$state}()
            ->create();
    }

    #[Test]
    public function file_kalender_diunduh_dengan_content_type_kalender(): void
    {
        $invitation = $this->invitation();
        InvitationEvent::factory()->akad()->for($invitation)->create();

        $response = $this->get(route('invitation.ics', $invitation->slug));

        $response->assertOk();
        $this->assertSame('text/calendar; charset=utf-8', $response->headers->get('content-type'));
        $this->assertStringContainsString(
            'filename="undangan-'.$invitation->slug.'.ics"',
            (string) $response->headers->get('content-disposition'),
        );
    }

    #[Test]
    public function jam_acara_ditulis_dalam_utc_sesuai_zona_waktu_acara(): void
    {
        $invitation = $this->invitation();

        // 08.00 WIB = 01.00 UTC. Pergeseran tujuh jam di sini akan membuat
        // pengingat kalender setiap tamu salah.
        InvitationEvent::factory()->akad()->for($invitation)->create([
            'starts_at' => Carbon::parse('2026-12-12 08:00', 'Asia/Jakarta')->utc(),
            'ends_at' => null,
            'timezone' => 'Asia/Jakarta',
        ]);

        $body = $this->get(route('invitation.ics', $invitation->slug))->getContent();

        $this->assertStringContainsString('DTSTART:20261212T010000Z', $body);
        // Tanpa jam selesai, durasi bawaan dari config yang dipakai.
        $expectedEnd = Carbon::parse('2026-12-12 01:00', 'UTC')
            ->addHours((int) config('invitation.ics_default_duration_hours'))
            ->format('Ymd\THis\Z');
        $this->assertStringContainsString('DTEND:'.$expectedEnd, $body);
    }

    #[Test]
    public function setiap_acara_mendapat_satu_vevent(): void
    {
        $invitation = $this->invitation();
        InvitationEvent::factory()->akad()->for($invitation)->create();
        InvitationEvent::factory()->resepsi()->for($invitation)->create();

        $body = $this->get(route('invitation.ics', $invitation->slug))->getContent();

        $this->assertSame(2, substr_count($body, 'BEGIN:VEVENT'));
        $this->assertSame(2, substr_count($body, 'END:VEVENT'));
        $this->assertStringStartsWith("BEGIN:VCALENDAR\r\n", $body);
        $this->assertStringEndsWith("END:VCALENDAR\r\n", $body);
    }

    #[Test]
    public function karakter_khusus_pada_nama_tempat_di_escape(): void
    {
        $invitation = $this->invitation();
        InvitationEvent::factory()->akad()->for($invitation)->create([
            'venue_name' => 'Gedung A, Lantai 2; Sayap Timur',
            'address' => "Jl. Melati No. 1\nBantul",
        ]);

        $body = $this->get(route('invitation.ics', $invitation->slug))->getContent();

        // Koma dan titik koma wajib di-escape, kalau tidak parser kalender
        // membaca sisanya sebagai parameter properti dan baris rusak.
        $this->assertStringContainsString('Gedung A\\, Lantai 2\\; Sayap Timur', $body);
        $this->assertStringNotContainsString("Jl. Melati No. 1\nBantul", $body);
    }

    #[Test]
    public function baris_panjang_dilipat_pada_75_oktet(): void
    {
        $invitation = $this->invitation();
        InvitationEvent::factory()->akad()->for($invitation)->create([
            'venue_name' => str_repeat('Gedung Serbaguna Panjang ', 8),
        ]);

        $body = $this->get(route('invitation.ics', $invitation->slug))->getContent();

        foreach (explode("\r\n", $body) as $line) {
            $this->assertLessThanOrEqual(75, strlen($line), "Baris melebihi 75 oktet: {$line}");
        }
    }

    #[Test]
    public function undangan_tanpa_acara_menjawab_404(): void
    {
        $invitation = $this->invitation();

        $this->get(route('invitation.ics', $invitation->slug))->assertNotFound();
    }

    #[Test]
    public function undangan_kedaluwarsa_tidak_lagi_menyediakan_file_kalender(): void
    {
        $invitation = $this->invitation('expired');
        InvitationEvent::factory()->akad()->for($invitation)->create();

        $this->get(route('invitation.ics', $invitation->slug))->assertNotFound();
    }

    #[Test]
    public function draft_menyediakan_file_kalender_lewat_signed_url(): void
    {
        $invitation = Invitation::factory()->for(Template::factory()->renderable())->create();
        InvitationEvent::factory()->akad()->for($invitation)->create();

        $this->get(route('invitation.ics', $invitation->slug))->assertNotFound();

        $this->get(URL::signedRoute('invitation.ics', ['slug' => $invitation->slug]))->assertOk();
    }
}
