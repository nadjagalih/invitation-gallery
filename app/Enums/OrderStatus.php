<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasColor, HasLabel
{
    case Pending = 'pending';
    case WaitingPayment = 'waiting_payment';
    case Paid = 'paid';
    case InProgress = 'in_progress';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::WaitingPayment => 'Menunggu Pembayaran',
            self::Paid => 'Sudah Dibayar',
            self::InProgress => 'Sedang Dikerjakan',
            self::Delivered => 'Terkirim',
            self::Cancelled => 'Dibatalkan',
            self::Refunded => 'Dana Dikembalikan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending, self::WaitingPayment => 'warning',
            self::Paid, self::InProgress => 'info',
            self::Delivered => 'success',
            self::Cancelled, self::Refunded => 'danger',
        };
    }

    /**
     * Status yang berarti uangnya sudah masuk. Satu-satunya daftarnya;
     * Order::isPaid() meneruskan ke sini.
     */
    public function isPaid(): bool
    {
        return in_array($this, [self::Paid, self::InProgress, self::Delivered], true);
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
