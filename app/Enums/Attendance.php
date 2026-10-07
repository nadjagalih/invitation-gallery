<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum Attendance: string implements HasColor, HasLabel
{
    case Attending = 'attending';
    case NotAttending = 'not_attending';
    case Tentative = 'tentative';

    public function label(): string
    {
        return match ($this) {
            self::Attending => 'Hadir',
            self::NotAttending => 'Tidak Hadir',
            self::Tentative => 'Masih Ragu',
        };
    }

    /** Warna badge di panel admin. */
    public function color(): string
    {
        return match ($this) {
            self::Attending => 'success',
            self::NotAttending => 'danger',
            self::Tentative => 'warning',
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
