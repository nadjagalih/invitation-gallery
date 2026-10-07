<?php

namespace Tests\Feature\Filament;

use App\Enums\InvitationStatus;
use App\Filament\Resources\Invitations\Pages\CreateInvitation;
use App\Models\Invitation;
use App\Models\Template;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\PanelTestCase;

class InvitationPublicationTest extends PanelTestCase
{
    private function formData(Template $template, string $status): array
    {
        return [
            'template_id' => $template->getKey(),
            'template_version' => 1,
            'slug' => 'bagas-sari',
            'status' => $status,
            'asset_state' => 'retained',
            'asset_disk' => 'public',
            'groom_nickname' => 'Bagas',
            'groom_full_name' => 'Bagas Prasetyo',
            'groom_father' => 'Bapak Suryanto',
            'groom_mother' => 'Ibu Sri Wahyuni',
            'bride_nickname' => 'Sari',
            'bride_full_name' => 'Sari Handayani',
            'bride_father' => 'Bapak Handoko',
            'bride_mother' => 'Ibu Retno',
            'event_date' => '2026-12-12',
            'grace_days' => 30,
            'events' => [[
                'title' => 'Akad Nikah',
                'timezone' => 'Asia/Jakarta',
                'starts_at' => '2026-12-12 08:00',
                'venue_name' => 'Masjid Agung',
                'address' => 'Jalan Merdeka 1, Semarang',
                'is_primary' => true,
            ]],
        ];
    }

    #[Test]
    public function memilih_status_aktif_menerbitkan_dan_mengisi_masa_aktif(): void
    {
        $template = Template::factory()->create();

        Livewire::test(CreateInvitation::class)
            ->fillForm($this->formData($template, InvitationStatus::Active->value))
            ->call('create')
            ->assertHasNoFormErrors();

        $invitation = Invitation::query()->sole();

        $this->assertSame(InvitationStatus::Active, $invitation->status);
        $this->assertNotNull($invitation->published_at);
        $this->assertNotNull($invitation->slug_locked_at);
        $this->assertSame(
            Carbon::parse('2026-12-12')->endOfDay()->addDays(config('invitation.active_days'))->toDateTimeString(),
            $invitation->expires_at->toDateTimeString(),
        );
        $this->assertSame(
            $invitation->expires_at->copy()->addDays(30)->toDateTimeString(),
            $invitation->asset_delete_at->toDateTimeString(),
        );
    }

    #[Test]
    public function memilih_status_preview_menerbitkan_demo_tanpa_membuka_tulisan(): void
    {
        $template = Template::factory()->create();

        Livewire::test(CreateInvitation::class)
            ->fillForm($this->formData($template, InvitationStatus::Preview->value))
            ->call('create')
            ->assertHasNoFormErrors();

        $invitation = Invitation::query()->sole();

        $this->assertSame(InvitationStatus::Preview, $invitation->status);
        $this->assertNotNull($invitation->published_at);
        $this->assertNotNull($invitation->slug_locked_at);
        $this->assertNull($invitation->expires_at);
        $this->assertFalse($invitation->status->acceptsWrites());
    }
}
