<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Invitations\Pages\EditInvitation;
use App\Filament\Resources\Invitations\RelationManagers\WishesRelationManager;
use App\Filament\Resources\Wishes\Pages\ListWishes;
use App\Models\Invitation;
use App\Models\Template;
use App\Models\Wish;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\PanelTestCase;

/**
 * Moderasi ucapan punya dua pintu masuk: antrean lintas undangan di
 * WishResource, dan relation manager di halaman satu undangan. Keduanya memakai
 * aksi yang sama dari WishModeration, dan keduanya diuji di sini karena yang
 * dijaga adalah satu hal — hanya ucapan yang disetujui boleh tampil ke tamu.
 */
class WishModerationTest extends PanelTestCase
{
    private function invitation(): Invitation
    {
        return Invitation::factory()->for(Template::factory())->create();
    }

    #[Test]
    public function ucapan_menunggu_bisa_disetujui_dari_antrean(): void
    {
        $wish = Wish::factory()->for($this->invitation())->pending()->create();

        Livewire::test(ListWishes::class)
            ->callAction(TestAction::make('approve')->table($wish));

        $this->assertTrue($wish->refresh()->is_approved);
    }

    #[Test]
    public function ucapan_yang_sudah_tampil_bisa_disembunyikan(): void
    {
        $wish = Wish::factory()->for($this->invitation())->create();

        // Tab bawaan hanya memuat yang menunggu, jadi barisnya dicari di tab
        // sebelahnya.
        Livewire::test(ListWishes::class)
            ->set('activeTab', 'tampil')
            ->callAction(TestAction::make('unapprove')->table($wish));

        $this->assertFalse($wish->refresh()->is_approved);
    }

    #[Test]
    public function tiap_aksi_hanya_muncul_pada_keadaan_yang_relevan(): void
    {
        $pending = Wish::factory()->for($this->invitation())->pending()->create();
        $approved = Wish::factory()->for($this->invitation())->create();

        Livewire::test(ListWishes::class)
            ->set('activeTab', 'semua')
            ->assertActionVisible(TestAction::make('approve')->table($pending))
            ->assertActionHidden(TestAction::make('unapprove')->table($pending))
            ->assertActionVisible(TestAction::make('unapprove')->table($approved))
            ->assertActionHidden(TestAction::make('approve')->table($approved));
    }

    #[Test]
    public function aksi_massal_menyetujui_seluruh_pilihan(): void
    {
        $invitation = $this->invitation();
        $wishes = Wish::factory()->for($invitation)->pending()->count(3)->create();

        Livewire::test(ListWishes::class)
            ->callTableBulkAction('approveSelected', $wishes);

        $this->assertSame(0, $invitation->wishes()->pending()->count());
    }

    #[Test]
    public function aksi_massal_menyembunyikan_seluruh_pilihan(): void
    {
        $invitation = $this->invitation();
        $wishes = Wish::factory()->for($invitation)->count(3)->create();

        Livewire::test(ListWishes::class)
            ->set('activeTab', 'tampil')
            ->callTableBulkAction('unapproveSelected', $wishes);

        $this->assertSame(0, $invitation->wishes()->approved()->count());
    }

    #[Test]
    public function aksi_massal_tidak_menyentuh_ucapan_di_luar_pilihan(): void
    {
        $invitation = $this->invitation();
        $dipilih = Wish::factory()->for($invitation)->pending()->create();
        $tidakDipilih = Wish::factory()->for($invitation)->pending()->create();

        Livewire::test(ListWishes::class)
            ->callTableBulkAction('approveSelected', [$dipilih]);

        $this->assertTrue($dipilih->refresh()->is_approved);
        $this->assertFalse($tidakDipilih->refresh()->is_approved);
    }

    #[Test]
    public function tab_pertama_antrean_hanya_menampilkan_yang_menunggu(): void
    {
        $invitation = $this->invitation();
        $pending = Wish::factory()->for($invitation)->pending()->create();
        $approved = Wish::factory()->for($invitation)->create();

        // Tab pertama menjadi tab aktif bawaan, dan yang dicari di antrean
        // moderasi adalah yang belum diputuskan.
        Livewire::test(ListWishes::class)
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$approved]);
    }

    #[Test]
    public function relation_manager_memakai_aksi_moderasi_yang_sama(): void
    {
        $invitation = $this->invitation();
        $wish = Wish::factory()->for($invitation)->pending()->create();

        Livewire::test(WishesRelationManager::class, [
            'ownerRecord' => $invitation,
            'pageClass' => EditInvitation::class,
        ])->callAction(TestAction::make('approve')->table($wish));

        $this->assertTrue($wish->refresh()->is_approved);
    }

    #[Test]
    public function relation_manager_hanya_menampilkan_ucapan_undangannya(): void
    {
        $invitation = $this->invitation();
        $milikSendiri = Wish::factory()->for($invitation)->create();
        $undanganLain = Wish::factory()->for($this->invitation())->create();

        Livewire::test(WishesRelationManager::class, [
            'ownerRecord' => $invitation,
            'pageClass' => EditInvitation::class,
        ])
            ->assertCanSeeTableRecords([$milikSendiri])
            ->assertCanNotSeeTableRecords([$undanganLain]);
    }

    #[Test]
    public function badge_tab_menghitung_ucapan_yang_menunggu(): void
    {
        $invitation = $this->invitation();
        Wish::factory()->for($invitation)->pending()->count(2)->create();
        Wish::factory()->for($invitation)->create();

        $this->assertSame('2', WishesRelationManager::getBadge($invitation, EditInvitation::class));
    }

    #[Test]
    public function badge_tab_kosong_bila_tidak_ada_yang_menunggu(): void
    {
        $invitation = $this->invitation();
        Wish::factory()->for($invitation)->create();

        // Badge 0 tidak memberi informasi apa pun.
        $this->assertNull(WishesRelationManager::getBadge($invitation, EditInvitation::class));
    }
}
