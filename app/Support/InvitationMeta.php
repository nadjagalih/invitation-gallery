<?php

namespace App\Support;

use App\Models\Invitation;

/**
 * Meta Open Graph per undangan. Link undangan hidup dari sebaran di WhatsApp
 * dan preview WhatsApp tidak menjalankan JavaScript, jadi nilai-nilai ini harus
 * sudah ada di markup yang dikirim server.
 *
 * @phpstan-type MetaPayload array{title: string, description: string, image: ?string, url: string, site_name: string}
 */
final class InvitationMeta
{
    /** @return array<string, mixed> */
    public static function for(Invitation $invitation, string $guestName): array
    {
        $override = $invitation->meta ?? [];
        $couple = $invitation->coupleNames();

        // Setiap tamu melihat namanya sendiri pada preview WhatsApp.
        $title = $override['og_title'] ?? $invitation->title();
        $title = "{$title} — Undangan untuk {$guestName}";

        $description = $override['og_description']
            ?? "{$couple} akan melangsungkan pernikahan pada {$invitation->eventDateLabel()}. Kami mengundang Anda untuk hadir dan memberikan doa restu.";

        return [
            'title' => $title,
            'description' => $description,
            'image' => $override['og_image'] ?? $invitation->ogImageUrl(),
            'url' => $invitation->publicUrl(),
            'site_name' => (string) config('invitation.brand.name'),
        ];
    }

    /** Meta untuk halaman non-undangan (expired, archived) tanpa membocorkan foto. */
    public static function plain(string $title, string $description, string $url): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'image' => null,
            'url' => $url,
            'site_name' => (string) config('invitation.brand.name'),
        ];
    }
}
