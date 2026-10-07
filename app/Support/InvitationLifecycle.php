<?php

namespace App\Support;

use Illuminate\Support\Carbon;

/**
 * Perhitungan tanggal masa aktif, satu tempat. Dipakai factory, Filament,
 * scheduler, dan layanan perpanjangan supaya tidak ada dua versi rumus.
 */
final class InvitationLifecycle
{
    /** Masa aktif dihitung dari tanggal acara, bukan dari tanggal publikasi. */
    public static function expiryFor(Carbon|\DateTimeInterface|string $eventDate, ?int $activeDays = null): Carbon
    {
        $days = $activeDays ?? (int) config('invitation.active_days');

        return Carbon::parse($eventDate)->endOfDay()->addDays($days);
    }

    public static function assetDeleteFor(Carbon|\DateTimeInterface|string $expiresAt, ?int $graceDays = null): Carbon
    {
        $days = $graceDays ?? (int) config('invitation.grace_days');

        return Carbon::parse($expiresAt)->addDays($days);
    }

    /** Batas H-n sebelum asset_delete_at untuk peringatan terakhir. */
    public static function warningThreshold(?int $warnDays = null): Carbon
    {
        $days = $warnDays ?? (int) config('invitation.warn_days');

        return now()->addDays($days);
    }
}
