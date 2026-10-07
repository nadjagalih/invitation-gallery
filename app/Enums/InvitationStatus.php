<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle publik undangan — mengurus apa yang dilihat pengunjung.
 * Berjalan independen dari AssetState yang mengurus keberadaan file.
 */
enum InvitationStatus: string implements HasColor, HasLabel
{
    case Draft = 'draft';
    case Preview = 'preview';
    case Active = 'active';
    case Expired = 'expired';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Preview => 'Preview / Demo',
            self::Active => 'Aktif',
            self::Expired => 'Masa Aktif Berakhir',
            self::Archived => 'Diarsipkan',
        };
    }

    /** Warna badge di panel admin. */
    public function color(): string
    {
        return match ($this) {
            self::Draft => 'gray',
            self::Preview => 'info',
            self::Active => 'success',
            self::Expired => 'warning',
            self::Archived => 'danger',
        };
    }

    /**
     * Kontrak Filament. Diteruskan ke label()/color() supaya badge di panel
     * mengambil teks dan warnanya sendiri, sementara view dan test yang sudah
     * memanggil label() tetap jalan.
     */
    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return $this->color();
    }

    /** Undangan penuh dirender: preview memakai data demo, active data asli. */
    public function rendersInvitation(): bool
    {
        return in_array($this, [self::Preview, self::Active], true);
    }

    /**
     * Hanya `active` yang menerima RSVP dan ucapan. `preview` sengaja menolak:
     * demo katalog dibuka banyak orang asing dan daftar ucapannya akan penuh sampah.
     */
    public function acceptsWrites(): bool
    {
        return $this === self::Active;
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
