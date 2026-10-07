<?php

namespace Database\Seeders;

use App\Enums\AssetState;
use App\Enums\InvitationFeature;
use App\Enums\InvitationStatus;
use App\Models\Invitation;
use App\Models\Template;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

/**
 * Satu undangan demo berstatus `preview`: dirender penuh dengan data demo tapi
 * menolak RSVP dan ucapan, karena demo katalog dibuka banyak orang asing.
 */
class DemoInvitationSeeder extends Seeder
{
    public function run(): void
    {
        $template = Template::where('slug', 'elegant-botanical')->firstOrFail();
        $eventDate = Carbon::parse('2026-12-12');

        $invitation = Invitation::updateOrCreate(
            ['slug' => 'demo-elegant-botanical'],
            [
                'user_id' => null,
                'template_id' => $template->id,
                'template_version' => $template->current_version,
                'status' => InvitationStatus::Preview,
                'asset_state' => AssetState::Retained,
                'asset_disk' => config('invitation.asset_disk'),
                'features' => InvitationFeature::defaults(),

                'groom_nickname' => 'Bagas',
                'groom_full_name' => 'Bagas Prasetyo, S.T.',
                'groom_child_order' => 'Pertama',
                'groom_father' => 'Bapak Suryanto',
                'groom_mother' => 'Ibu Sri Wahyuni',
                'groom_instagram' => 'bagasprasetyo',

                'bride_nickname' => 'Ayu',
                'bride_full_name' => 'Ayu Lestari, S.Pd.',
                'bride_child_order' => 'Kedua',
                'bride_father' => 'Bapak Hendra Kusuma',
                'bride_mother' => 'Ibu Dewi Anggraini',
                'bride_instagram' => 'ayulestari',

                'event_date' => $eventDate,
                'quote_text' => 'Dan di antara tanda-tanda kekuasaan-Nya ialah Dia menciptakan untukmu pasangan dari jenismu sendiri, agar kamu merasa tenteram kepadanya. Dan dijadikan-Nya di antaramu rasa kasih dan sayang.',
                'quote_source' => 'QS. Ar-Rum: 21',
                'opening_words' => 'Dengan memohon rahmat dan ridho Allah Subhanahu Wa Ta\'ala, kami bermaksud menyelenggarakan pernikahan putra-putri kami. Merupakan suatu kehormatan dan kebahagiaan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu.',
                'closing_words' => 'Merupakan suatu kebahagiaan dan kehormatan bagi kami apabila Bapak/Ibu/Saudara/i berkenan hadir dan memberikan doa restu kepada kami.',
                'gift_address' => 'Jl. Melati Indah No. 24, Kelurahan Sukamaju, Kecamatan Bantul, Yogyakarta 55711',
                'moderate_wishes' => true,

                'published_at' => null,
                'expires_at' => null,
                'grace_days' => config('invitation.grace_days'),
                'asset_delete_at' => null,
                'meta' => [
                    'og_title' => 'Undangan Pernikahan Bagas & Ayu',
                    'og_description' => 'Contoh undangan digital dengan desain Elegant Botanical dari '.config('invitation.brand.name').'.',
                ],
            ],
        );

        $this->seedEvents($invitation, $eventDate);
        $this->seedStories($invitation);
        $this->seedBankAccounts($invitation);
        $this->seedWishes($invitation);

        // Tombol "Lihat" pada kartu katalog menunjuk ke sini.
        $template->update(['demo_invitation_id' => $invitation->id]);
    }

    private function seedEvents(Invitation $invitation, Carbon $eventDate): void
    {
        $invitation->events()->delete();

        // Jam ditulis dalam WIB lalu dikonversi ke UTC untuk disimpan. Membangun
        // Carbon di zona waktu server lalu memanggil setTimezone('UTC') justru
        // menggeser jamnya: 08.00 akan tersimpan sebagai 08.00 UTC dan tampil
        // 15.00 WIB di countdown maupun file kalender.
        $wib = fn (int $hour, int $minute = 0) => Carbon::createFromFormat(
            'Y-m-d H:i',
            $eventDate->format('Y-m-d').' '.sprintf('%02d:%02d', $hour, $minute),
            'Asia/Jakarta',
        )->utc();

        $invitation->events()->createMany([
            [
                'title' => 'Akad Nikah',
                'starts_at' => $wib(8),
                'ends_at' => null,
                'timezone' => 'Asia/Jakarta',
                'venue_name' => 'Kediaman Mempelai Wanita',
                'address' => 'Jl. Melati Indah No. 24, Kelurahan Sukamaju, Kecamatan Bantul, Yogyakarta',
                'maps_url' => 'https://maps.google.com/?q=-7.8878,110.3268',
                'is_primary' => true,
                'sort_order' => 0,
            ],
            [
                'title' => 'Resepsi',
                'starts_at' => $wib(11),
                'ends_at' => $wib(14),
                'timezone' => 'Asia/Jakarta',
                'venue_name' => 'Pendopo Argo Wilis',
                'address' => 'Jl. Parangtritis KM 8, Sewon, Bantul, Yogyakarta',
                'maps_url' => 'https://maps.google.com/?q=-7.8712,110.3601',
                'is_primary' => false,
                'sort_order' => 1,
            ],
        ]);
    }

    private function seedStories(Invitation $invitation): void
    {
        $invitation->stories()->delete();

        $invitation->stories()->createMany([
            [
                'title' => 'Awal Bertemu',
                'happened_on' => '2019-03-01',
                'body' => 'Kami pertama kali bertemu di lingkungan kampus yang sama dan mulai saling mengenal lewat teman.',
                'sort_order' => 0,
            ],
            [
                'title' => 'Menjalin Hubungan',
                'happened_on' => '2021-07-01',
                'body' => 'Setelah saling memahami, kami memutuskan untuk menjalani hubungan yang lebih serius.',
                'sort_order' => 1,
            ],
            [
                'title' => 'Lamaran',
                'happened_on' => '2024-09-01',
                'body' => 'Doa dan restu kedua keluarga menjadi awal langkah kami menuju jenjang pernikahan.',
                'sort_order' => 2,
            ],
            [
                'title' => 'Menikah',
                'happened_on' => '2026-12-01',
                'body' => 'Dengan izin Allah, kami akan melangkah ke jenjang pernikahan dan memulai kehidupan baru.',
                'sort_order' => 3,
            ],
        ]);
    }

    private function seedBankAccounts(Invitation $invitation): void
    {
        $invitation->bankAccounts()->delete();

        $invitation->bankAccounts()->createMany([
            [
                'bank_name' => 'Bank BCA',
                'account_number' => '1234567890',
                'account_holder' => 'Bagas Prasetyo',
                'sort_order' => 0,
            ],
            [
                'bank_name' => 'Bank Mandiri',
                'account_number' => '0987654321',
                'account_holder' => 'Ayu Lestari',
                'sort_order' => 1,
            ],
        ]);
    }

    private function seedWishes(Invitation $invitation): void
    {
        $invitation->wishes()->delete();

        $invitation->wishes()->createMany([
            [
                'name' => config('invitation.brand.name'),
                'message' => 'Selamat menempuh hidup baru, semoga sakinah mawaddah warahmah.',
                'is_approved' => true,
            ],
            [
                'name' => 'Rizki & Keluarga',
                'message' => 'Barakallahu lakuma wa baraka alaikuma wa jamaa bainakuma fii khairin.',
                'is_approved' => true,
            ],
        ]);
    }
}
