<?php

namespace App\Support;

use App\Models\Invitation;
use App\Models\InvitationEvent;
use Illuminate\Support\Str;

/**
 * File .ics untuk tombol "Simpan ke Kalender".
 *
 * Dibuat di server, bukan sebagai Blob di browser seperti template HTML lama:
 * sebagian versi Safari iOS menolak unduhan Blob dan tamu berakhir dengan file
 * kosong, sementara URL biasa dibuka langsung oleh aplikasi kalender.
 */
final class IcsBuilder
{
    public static function forInvitation(Invitation $invitation): string
    {
        $lines = [
            'BEGIN:VCALENDAR',
            'VERSION:2.0',
            'PRODID:-//'.config('invitation.brand.name').'//Undangan Digital//ID',
            'CALSCALE:GREGORIAN',
            'METHOD:PUBLISH',
            'X-WR-CALNAME:'.self::escape($invitation->title()),
        ];

        foreach ($invitation->events as $event) {
            $lines = array_merge($lines, self::eventLines($invitation, $event));
        }

        $lines[] = 'END:VCALENDAR';

        // RFC 5545 mewajibkan CRLF, bukan LF.
        return collect($lines)->map(fn (string $line) => self::fold($line))->implode("\r\n")."\r\n";
    }

    public static function filenameFor(Invitation $invitation): string
    {
        return 'undangan-'.$invitation->slug.'.ics';
    }

    /** @return array<int, string> */
    private static function eventLines(Invitation $invitation, InvitationEvent $event): array
    {
        $start = $event->starts_at->clone()->utc();
        $end = $event->ends_at?->clone()->utc()
            ?? $start->clone()->addHours((int) config('invitation.ics_default_duration_hours'));

        $description = trim(implode("\n", array_filter([
            $invitation->title(),
            $event->notes,
            $invitation->publicUrl(),
        ])));

        return [
            'BEGIN:VEVENT',
            'UID:'.self::uid($invitation, $event),
            'DTSTAMP:'.now()->utc()->format('Ymd\THis\Z'),
            'DTSTART:'.$start->format('Ymd\THis\Z'),
            'DTEND:'.$end->format('Ymd\THis\Z'),
            'SUMMARY:'.self::escape($event->title.' — '.$invitation->coupleNames()),
            'DESCRIPTION:'.self::escape($description),
            'LOCATION:'.self::escape($event->venue_name.', '.$event->address),
            'URL:'.self::escape($invitation->publicUrl()),
            'END:VEVENT',
        ];
    }

    /**
     * UID stabil: mengimpor ulang file yang sama memperbarui entri lama alih-alih
     * menumpuk duplikat di kalender tamu.
     */
    private static function uid(Invitation $invitation, InvitationEvent $event): string
    {
        $host = Str::of(config('app.url'))->after('://')->before('/')->value() ?: 'localhost';

        return "invitation-{$invitation->id}-event-{$event->id}@{$host}";
    }

    private static function escape(string $value): string
    {
        return str_replace(
            ['\\', ';', ',', "\r\n", "\n", "\r"],
            ['\\\\', '\;', '\,', '\n', '\n', '\n'],
            $value,
        );
    }

    /**
     * Baris di atas 75 oktet dilipat; baris lanjutan diawali satu spasi.
     * Pemotongan dihitung per karakter UTF-8 agar tidak membelah multibyte.
     */
    private static function fold(string $line): string
    {
        if (strlen($line) <= 75) {
            return $line;
        }

        $chunks = [];
        $current = '';
        $limit = 75;

        foreach (preg_split('//u', $line, -1, PREG_SPLIT_NO_EMPTY) ?: [] as $char) {
            if (strlen($current) + strlen($char) > $limit) {
                $chunks[] = $current;
                $current = '';
                $limit = 74;
            }

            $current .= $char;
        }

        $chunks[] = $current;

        return implode("\r\n ", $chunks);
    }
}
