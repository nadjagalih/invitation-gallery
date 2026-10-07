<?php

namespace App\Enums;

/**
 * Daftar fitur undangan, satu tempat. Dipakai tiga sisi yang harus sepakat:
 * `templates.supported_features` (desain mampu merender), `invitations.features`
 * (pelanggan berhak memakai), dan `orders.addons` (fitur yang dibeli).
 *
 * Sebelum ada enum ini daftarnya ditulis ulang di factory, seeder, dan form
 * admin — tiga tempat yang pasti bergeser satu dari yang lain.
 */
enum InvitationFeature: string
{
    case LoveStory = 'love_story';
    case Gallery = 'gallery';
    case Gift = 'gift';
    case GiftConfirmation = 'gift_confirmation';
    case Rsvp = 'rsvp';
    case Wishes = 'wishes';
    case Music = 'music';
    case RsvpKit = 'rsvpkit';

    public function label(): string
    {
        return match ($this) {
            self::LoveStory => 'Love Story',
            self::Gallery => 'Galeri Foto',
            self::Gift => 'Amplop Digital',
            self::GiftConfirmation => 'Konfirmasi Kado',
            self::Rsvp => 'RSVP',
            self::Wishes => 'Ucapan & Doa',
            self::Music => 'Musik Latar',
            self::RsvpKit => 'RSVPKIT (link per tamu)',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::LoveStory => 'Linimasa perjalanan pasangan.',
            self::Gallery => 'Galeri foto dengan lightbox.',
            self::Gift => 'Nomor rekening dan alamat kirim kado.',
            self::GiftConfirmation => 'Form konfirmasi transfer beserta bukti.',
            self::Rsvp => 'Form konfirmasi kehadiran tamu.',
            self::Wishes => 'Form dan daftar ucapan tamu.',
            self::Music => 'Musik latar yang diputar setelah undangan dibuka.',
            self::RsvpKit => 'Belum aktif di MVP; kolomnya sudah tersedia.',
        };
    }

    /** @return array<string, string> */
    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            [],
        );
    }

    /** @return array<string, string> */
    public static function descriptions(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->description()],
            [],
        );
    }

    /** @return array<int, string> */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Fitur yang tidak termasuk paket dasar, jadi harus dibeli lewat order.
     * Satu-satunya sumber daftar ini; defaults() dan form add-on pada order
     * keduanya membacanya dari sini.
     *
     * @return array<string, string>
     */
    public static function addOns(): array
    {
        $addOns = [self::RsvpKit, self::Music];

        return array_reduce(
            $addOns,
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            [],
        );
    }

    /**
     * Fitur yang menyala untuk undangan baru: semua yang dijual sebagai bagian
     * paket dasar. Add-on berbayar sengaja dimatikan sampai order membukanya.
     *
     * @return array<string, bool>
     */
    public static function defaults(): array
    {
        $addOns = array_keys(self::addOns());

        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [
                $case->value => ! in_array($case->value, $addOns, true),
            ],
            [],
        );
    }
}
