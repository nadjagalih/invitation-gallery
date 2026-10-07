<?php

namespace App\Support;

use App\Enums\InvitationStatus;
use App\Models\Invitation;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

final class InvitationPublication
{
    /**
     * Apply publication side effects when a draft becomes preview or active.
     * The status field remains the admin's single publication control.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function prepare(array $data, ?Invitation $existing = null): array
    {
        $status = InvitationStatus::tryFrom((string) ($data['status'] ?? ''));
        $isPublished = in_array($status, [InvitationStatus::Preview, InvitationStatus::Active], true);

        if (! $isPublished) {
            return $data;
        }

        // Edit form tidak selalu mengirim ulang field disabled pada record yang
        // sudah terbit. Nilai publikasinya sudah tersimpan, jadi validasi wajib
        // hanya berlaku ketika draft benar-benar diterbitkan pertama kali.
        if (! $existing?->isSlugLocked()) {
            self::validate($data, $existing);
        }

        $now = now();
        $data['published_at'] ??= $now;
        $data['slug_locked_at'] ??= $now;

        if ($status === InvitationStatus::Active) {
            $expiresAt = self::date($data['expires_at'] ?? null)
                ?? InvitationLifecycle::expiryFor($data['event_date']);

            $data['expires_at'] = $expiresAt;
            $data['asset_delete_at'] ??= InvitationLifecycle::assetDeleteFor(
                $expiresAt,
                (int) ($data['grace_days'] ?? config('invitation.grace_days')),
            );
        }

        return $data;
    }

    /** @param  array<string, mixed>  $data */
    private static function validate(array $data, ?Invitation $existing): void
    {
        $missing = [];

        foreach (['template_id', 'template_version', 'slug', 'event_date'] as $field) {
            if (blank($data[$field] ?? null)) {
                $missing[$field] = 'Wajib diisi sebelum undangan diterbitkan.';
            }
        }

        if ($existing?->isSlugLocked() && isset($data['slug']) && $data['slug'] !== $existing->slug) {
            $missing['slug'] = 'Slug sudah terkunci setelah publikasi.';
        }

        if ($missing !== []) {
            throw ValidationException::withMessages($missing);
        }
    }

    private static function date(mixed $value): ?Carbon
    {
        if (blank($value)) {
            return null;
        }

        return $value instanceof Carbon ? $value : Carbon::parse($value);
    }
}
