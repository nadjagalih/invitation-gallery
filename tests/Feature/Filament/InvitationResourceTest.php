<?php

namespace Tests\Feature\Filament;

use App\Enums\InvitationStatus;
use App\Enums\MediaCollection;
use App\Filament\Resources\Invitations\Pages\CreateInvitation;
use App\Filament\Resources\Invitations\Pages\EditInvitation;
use App\Filament\Resources\Invitations\Pages\ListInvitations;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use App\Models\Template;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use PHPUnit\Framework\Attributes\Test;
use Tests\PanelTestCase;

/**
 * Form undangan adalah form terbesar di panel: delapan tab, tiga repeater dengan
 * relationship, dan dua kolom yang sengaja tidak boleh menerima kiriman browser
 * apa adanya. Yang diuji di sini jalur simpannya, bukan tampilannya.
 */
class InvitationResourceTest extends PanelTestCase
{
    /** @return array<string, mixed> */
    private function formData(Template $template, array $overrides = []): array
    {
        return [
            'template_id' => $template->getKey(),
            'template_version' => 1,
            'slug' => 'bagas-sari',
            'status' => InvitationStatus::Draft->value,
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
            ...$overrides,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function eventRows(): array
    {
        return [[
            'title' => 'Akad Nikah',
            'timezone' => 'Asia/Jakarta',
            'starts_at' => '2026-12-12 08:00',
            'venue_name' => 'Masjid Agung',
            'address' => 'Jalan Merdeka 1, Semarang',
            'is_primary' => true,
        ]];
    }

    #[Test]
    public function undangan_baru_tersimpan_beserta_acaranya(): void
    {
        $template = Template::factory()->create();

        Livewire::test(CreateInvitation::class)
            ->fillForm($this->formData($template, ['events' => $this->eventRows()]))
            ->call('create')
            ->assertHasNoFormErrors();

        $invitation = Invitation::query()->sole();

        $this->assertSame('bagas-sari', $invitation->slug);
        $this->assertSame('Akad Nikah', $invitation->events()->sole()->title);
    }

    #[Test]
    public function fitur_tersimpan_sebagai_peta_bukan_daftar(): void
    {
        $template = Template::factory()->create();

        Livewire::test(CreateInvitation::class)
            ->fillForm($this->formData($template, [
                'events' => $this->eventRows(),
                'features' => ['gallery', 'rsvp'],
            ]))
            ->call('create')
            ->assertHasNoFormErrors();

        $features = Invitation::query()->sole()->features;

        // hasFeature() membaca kolomnya lewat data_get(), jadi bentuk peta bukan
        // pilihan gaya — daftar akan membuat setiap fitur mati.
        $this->assertTrue($features['gallery']);
        $this->assertTrue($features['rsvp']);
        $this->assertFalse($features['music']);
    }

    #[Test]
    public function versi_template_ikut_versi_terkini_saat_template_dipilih(): void
    {
        $template = Template::factory()->create(['current_version' => 3]);

        Livewire::test(CreateInvitation::class)
            ->fillForm(['template_id' => $template->getKey()])
            ->assertSchemaStateSet(['template_version' => 3]);
    }

    #[Test]
    public function cerita_dan_rekening_tersimpan_dari_repeaternya(): void
    {
        $invitation = Invitation::factory()->for(Template::factory())->create();

        Livewire::test(EditInvitation::class, ['record' => $invitation->getRouteKey()])
            ->fillForm([
                'stories' => [[
                    'title' => 'Pertemuan Pertama',
                    'body' => 'Bertemu di kampus.',
                    'happened_on' => '2019-03-01',
                ]],
                'bankAccounts' => [[
                    'bank_name' => 'BCA',
                    'account_number' => '0123456789',
                    'account_holder' => 'Bagas Prasetyo',
                ]],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('Pertemuan Pertama', $invitation->stories()->sole()->title);

        // Nomor rekening disimpan sebagai teks: nol di depan harus utuh.
        $this->assertSame('0123456789', $invitation->bankAccounts()->sole()->account_number);
    }

    #[Test]
    public function urutan_baris_repeater_tersimpan_sebagai_sort_order(): void
    {
        $invitation = Invitation::factory()->for(Template::factory())->create();

        Livewire::test(EditInvitation::class, ['record' => $invitation->getRouteKey()])
            ->fillForm([
                'stories' => [
                    ['title' => 'Babak Satu', 'body' => 'Awal.'],
                    ['title' => 'Babak Dua', 'body' => 'Lanjutan.'],
                ],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        // Relasi stories() sudah mengurutkan lewat sort_order, jadi urutan yang
        // terbaca inilah yang dilihat tamu.
        $this->assertSame(
            ['Babak Satu', 'Babak Dua'],
            $invitation->stories()->pluck('title')->all(),
        );
    }

    #[Test]
    public function slug_yang_terkunci_tidak_bisa_diubah_walau_kirimannya_dipaksa(): void
    {
        $invitation = Invitation::factory()->for(Template::factory())->active()->create();
        $slug = $invitation->slug;

        $this->assertTrue($invitation->isSlugLocked());

        // Field-nya disabled() di form, dan itu bisa ditembus dari sisi klien.
        // Kembaran sisi server inilah yang benar-benar menutupnya.
        Livewire::test(EditInvitation::class, ['record' => $invitation->getRouteKey()])
            ->fillForm(['slug' => 'slug-baru-hasil-suntingan'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame($slug, $invitation->refresh()->slug);
    }

    #[Test]
    public function slug_yang_belum_terkunci_masih_bisa_diubah(): void
    {
        $invitation = Invitation::factory()->for(Template::factory())->create();

        Livewire::test(EditInvitation::class, ['record' => $invitation->getRouteKey()])
            ->fillForm(['slug' => 'bagas-dan-sari'])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertSame('bagas-dan-sari', $invitation->refresh()->slug);
    }

    #[Test]
    public function slug_kembar_ditolak(): void
    {
        $template = Template::factory()->create();
        Invitation::factory()->for($template)->create(['slug' => 'bagas-sari']);

        Livewire::test(CreateInvitation::class)
            ->fillForm($this->formData($template, ['events' => $this->eventRows()]))
            ->call('create')
            ->assertHasFormErrors(['slug']);
    }

    #[Test]
    public function aksi_buat_slug_mengisi_dari_nama_mempelai(): void
    {
        $template = Template::factory()->create();

        Livewire::test(CreateInvitation::class)
            ->fillForm(['groom_nickname' => 'Bagas', 'bride_nickname' => 'Sari'])
            ->callAction(TestAction::make('generateSlug')->schemaComponent('slug'))
            ->assertSchemaStateSet(['slug' => 'bagas-sari']);

        $this->assertNotNull($template);
    }

    #[Test]
    public function media_baris_yang_tidak_lagi_ditunjuk_dibersihkan_setelah_simpan(): void
    {
        $invitation = Invitation::factory()->for(Template::factory())->create();

        $media = InvitationMedia::factory()->for($invitation)->create([
            'collection' => MediaCollection::Story,
        ]);

        $invitation->stories()->create([
            'title' => 'Pertemuan Pertama',
            'body' => 'Bertemu di kampus.',
            'media_id' => $media->getKey(),
        ]);

        // Barisnya dihapus, jadi tidak ada lagi yang menunjuk media itu. prune()
        // dari hook afterSave yang membuangnya.
        Livewire::test(EditInvitation::class, ['record' => $invitation->getRouteKey()])
            ->fillForm(['stories' => []])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseMissing('invitation_media', ['id' => $media->getKey()]);
    }

    #[Test]
    public function media_baris_yang_masih_ditunjuk_tidak_ikut_terbuang(): void
    {
        Storage::fake('local');

        $invitation = Invitation::factory()->for(Template::factory())->create();

        $media = InvitationMedia::factory()->for($invitation)->create([
            'collection' => MediaCollection::Story,
        ]);

        // Berkasnya harus benar-benar ada: field unggah membuang path yang
        // berkasnya hilang dari disk supaya tidak tampil sebagai kartu rusak.
        Storage::disk('local')->put($media->path, 'gambar');

        $invitation->stories()->create([
            'title' => 'Pertemuan Pertama',
            'body' => 'Bertemu di kampus.',
            'media_id' => $media->getKey(),
        ]);

        Livewire::test(EditInvitation::class, ['record' => $invitation->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('invitation_media', ['id' => $media->getKey()]);
        $this->assertSame($media->getKey(), $invitation->stories()->sole()->media_id);
    }

    #[Test]
    public function media_collection_lain_tidak_tersentuh_pembersihan_baris(): void
    {
        Storage::fake('local');

        $invitation = Invitation::factory()->for(Template::factory())->create();

        $cover = InvitationMedia::factory()->for($invitation)->create([
            'collection' => MediaCollection::Cover,
        ]);

        Storage::disk('local')->put($cover->path, 'gambar');

        // prune() hanya menyapu dua collection per-baris. Cover diurus MediaSync
        // lewat fieldnya sendiri, dan tidak boleh hilang karena repeater kosong.
        Livewire::test(EditInvitation::class, ['record' => $invitation->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('invitation_media', ['id' => $cover->getKey()]);
    }

    #[Test]
    public function media_yang_berkasnya_sudah_hilang_dari_disk_ikut_dibuang(): void
    {
        Storage::fake('local');

        $invitation = Invitation::factory()->for(Template::factory())->create();

        // Tidak ada berkas yang ditulis ke disk: barisnya menunjuk ke sesuatu
        // yang tidak ada lagi, dan menyimpan ulang membereskannya.
        $media = InvitationMedia::factory()->for($invitation)->create([
            'collection' => MediaCollection::Cover,
        ]);

        Livewire::test(EditInvitation::class, ['record' => $invitation->getRouteKey()])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseMissing('invitation_media', ['id' => $media->getKey()]);
    }

    #[Test]
    public function halaman_tambah_mengarahkan_ke_halaman_ubah(): void
    {
        $template = Template::factory()->create();

        // Field unggah butuh id undangan, jadi seluruhnya baru muncul di halaman
        // Edit. Mengantar admin ke sana adalah kelanjutan pekerjaan yang wajar.
        Livewire::test(CreateInvitation::class)
            ->fillForm($this->formData($template, ['events' => $this->eventRows()]))
            ->call('create')
            ->assertRedirect(EditInvitation::getUrl(['record' => Invitation::query()->sole()]));
    }

    #[Test]
    public function tab_segera_berakhir_hanya_memuat_yang_aktif_dan_dekat_kedaluwarsa(): void
    {
        $template = Template::factory()->create();

        $segera = Invitation::factory()->for($template)->active()->create([
            'expires_at' => now()->addDays(5),
        ]);

        $masihLama = Invitation::factory()->for($template)->active()->create([
            'expires_at' => now()->addDays(90),
        ]);

        $sudahBerakhir = Invitation::factory()->for($template)->expired()->create();

        Livewire::test(ListInvitations::class)
            ->set('activeTab', 'segera_berakhir')
            ->assertCanSeeTableRecords([$segera])
            ->assertCanNotSeeTableRecords([$masihLama, $sudahBerakhir]);
    }

    #[Test]
    public function undangan_yang_dihapus_lunak_tidak_muncul_di_daftar(): void
    {
        $invitation = Invitation::factory()->for(Template::factory())->create();
        $terhapus = Invitation::factory()->for(Template::factory())->create();
        $terhapus->delete();

        Livewire::test(ListInvitations::class)
            ->assertCanSeeTableRecords([$invitation])
            ->assertCanNotSeeTableRecords([$terhapus]);
    }
}
