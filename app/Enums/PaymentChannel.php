<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum PaymentChannel: string implements HasLabel
{
    case ManualTransfer = 'manual_transfer';
    case Midtrans = 'midtrans';

    public function label(): string
    {
        return match ($this) {
            self::ManualTransfer => 'Transfer Manual',
            self::Midtrans => 'Midtrans',
        };
    }

    /** Kontrak Filament; lihat catatan yang sama di InvitationStatus. */
    public function getLabel(): string
    {
        return $this->label();
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
