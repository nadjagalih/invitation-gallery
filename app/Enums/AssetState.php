<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Lifecycle asset undangan — mengurus keberadaan file, terpisah dari
 * InvitationStatus. Undangan `expired` bisa `retained` maupun
 * `deletion_pending`; undangan `archived` selalu `deleted`.
 */
enum AssetState: string implements HasColor, HasLabel
{
    case Retained = 'retained';
    case DeletionPending = 'deletion_pending';
    case Deleted = 'deleted';

    public function label(): string
    {
        return match ($this) {
            self::Retained => 'File Tersimpan',
            self::DeletionPending => 'Menunggu Penghapusan',
            self::Deleted => 'File Sudah Dihapus',
        };
    }

    /** Warna badge di panel admin. */
    public function color(): string
    {
        return match ($this) {
            self::Retained => 'success',
            self::DeletionPending => 'warning',
            self::Deleted => 'gray',
        };
    }

    /** Kontrak Filament; lihat catatan yang sama di InvitationStatus. */
    public function getLabel(): string
    {
        return $this->label();
    }

    public function getColor(): string
    {
        return $this->color();
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
