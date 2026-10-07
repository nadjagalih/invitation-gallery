<?php

namespace App\Enums;

enum MediaCollection: string
{
    case Cover = 'cover';
    case GroomPhoto = 'groom_photo';
    case BridePhoto = 'bride_photo';
    case Gallery = 'gallery';
    case Story = 'story';
    case Music = 'music';
    case BankLogo = 'bank_logo';
    case OgImage = 'og_image';

    public function label(): string
    {
        return match ($this) {
            self::Cover => 'Cover',
            self::GroomPhoto => 'Foto Mempelai Pria',
            self::BridePhoto => 'Foto Mempelai Wanita',
            self::Gallery => 'Galeri',
            self::Story => 'Cerita',
            self::Music => 'Musik',
            self::BankLogo => 'Logo Bank',
            self::OgImage => 'Gambar OG',
        };
    }

    /** Collection gambar diproses menjadi WebP beserta derivative. */
    public function isImage(): bool
    {
        return $this !== self::Music;
    }

    /** Direktori di bawah prefiks `invitations/{id}/`. */
    public function directory(): string
    {
        return $this->value;
    }

    public static function options(): array
    {
        return array_reduce(
            self::cases(),
            fn (array $carry, self $case) => $carry + [$case->value => $case->label()],
            [],
        );
    }
}
