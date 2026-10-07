<?php

namespace App\Support;

/**
 * Durasi MP3 dibaca langsung dari header framenya.
 *
 * `music_max_seconds` tidak bisa disimpulkan dari ukuran berkas: 5 MB berarti
 * tiga setengah menit pada 192 kbps tetapi setengah jam pada 32 kbps, dan yang
 * mengganggu tamu adalah durasinya, bukan byte-nya. ffprobe tidak selalu ada di
 * server produksi dan menarik dependensi seukuran getID3 untuk satu angka ini
 * tidak sebanding — sementara yang dibutuhkan hanya beberapa byte pertama.
 */
final class AudioDuration
{
    /** Jendela pencarian sync frame pertama, cukup untuk melewati artwork sisa. */
    private const SCAN_BYTES = 65536;

    /** Kbps per indeks bitrate: [grup versi][layer][indeks]. */
    private const BITRATES = [
        // MPEG 1
        1 => [
            1 => [0, 32, 64, 96, 128, 160, 192, 224, 256, 288, 320, 352, 384, 416, 448, 0],
            2 => [0, 32, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 384, 0],
            3 => [0, 32, 40, 48, 56, 64, 80, 96, 112, 128, 160, 192, 224, 256, 320, 0],
        ],
        // MPEG 2 dan 2.5, layer II dan III memakai tabel yang sama
        2 => [
            1 => [0, 32, 48, 56, 64, 80, 96, 112, 128, 144, 160, 176, 192, 224, 256, 0],
            2 => [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, 0],
            3 => [0, 8, 16, 24, 32, 40, 48, 56, 64, 80, 96, 112, 128, 144, 160, 0],
        ],
    ];

    /** Hz per indeks sample rate, dikunci versi MPEG. */
    private const SAMPLE_RATES = [
        1 => [44100, 48000, 32000],
        2 => [22050, 24000, 16000],
        25 => [11025, 12000, 8000],
    ];

    /**
     * Durasi dalam detik, atau null bila berkas tidak bisa dibaca sebagai MPEG
     * audio. Null berarti "tidak diketahui", bukan nol.
     */
    public static function seconds(string $absolutePath): ?int
    {
        if (! is_file($absolutePath)) {
            return null;
        }

        $fileSize = @filesize($absolutePath);

        if ($fileSize === false || $fileSize <= 0) {
            return null;
        }

        $handle = @fopen($absolutePath, 'rb');

        if ($handle === false) {
            return null;
        }

        try {
            $audioStart = self::id3v2Size($handle);
            $frame = self::findFrame($handle, $audioStart);

            if ($frame === null) {
                return null;
            }

            $audioBytes = $fileSize - $frame['offset'] - self::id3v1Size($handle, $fileSize);

            $seconds = self::durationFromToc($handle, $frame)
                ?? self::durationFromBitrate($frame, $audioBytes);

            return $seconds === null || $seconds <= 0 ? null : (int) ceil($seconds);
        } finally {
            fclose($handle);
        }
    }

    /**
     * Byte yang harus dilewati sebelum audio dimulai. Tag ID3v2 berisi judul,
     * artis, dan tidak jarang artwork beberapa ratus kilobyte — semuanya bukan
     * frame audio dan akan mengacaukan perhitungan bila ikut dihitung.
     *
     * @param  resource  $handle
     */
    private static function id3v2Size($handle): int
    {
        rewind($handle);
        $header = fread($handle, 10);

        if (! is_string($header) || strlen($header) < 10 || substr($header, 0, 3) !== 'ID3') {
            return 0;
        }

        // Ukuran ditulis sebagai syncsafe integer: bit tertinggi setiap byte
        // selalu nol supaya tidak pernah menyerupai frame sync.
        $bytes = unpack('C4', substr($header, 6, 4));
        $size = (($bytes[1] & 0x7F) << 21)
            | (($bytes[2] & 0x7F) << 14)
            | (($bytes[3] & 0x7F) << 7)
            | ($bytes[4] & 0x7F);

        $hasFooter = (ord($header[5]) & 0x10) !== 0;

        return 10 + $size + ($hasFooter ? 10 : 0);
    }

    /** @param  resource  $handle */
    private static function id3v1Size($handle, int $fileSize): int
    {
        if ($fileSize < 128) {
            return 0;
        }

        fseek($handle, $fileSize - 128);

        return fread($handle, 3) === 'TAG' ? 128 : 0;
    }

    /**
     * Frame audio pertama yang valid.
     *
     * Sync 11 bit bisa muncul secara kebetulan di dalam data biner, jadi
     * kandidat hanya diterima bila frame berikutnya juga dimulai dengan sync
     * pada jarak yang dihitung dari headernya sendiri.
     *
     * @param  resource  $handle
     * @return array{offset:int,mpeg:int,layer:int,bitrate:int,sampleRate:int,samplesPerFrame:int,frameLength:int,isMono:bool}|null
     */
    private static function findFrame($handle, int $from): ?array
    {
        fseek($handle, $from);
        $buffer = fread($handle, self::SCAN_BYTES);

        if (! is_string($buffer)) {
            return null;
        }

        $length = strlen($buffer);

        for ($i = 0; $i + 4 <= $length; $i++) {
            if ($buffer[$i] !== "\xFF" || (ord($buffer[$i + 1]) & 0xE0) !== 0xE0) {
                continue;
            }

            $frame = self::parseHeader(substr($buffer, $i, 4));

            if ($frame === null) {
                continue;
            }

            $next = $i + $frame['frameLength'];

            // Frame berikutnya di luar buffer: tidak bisa diverifikasi, tetapi
            // menolaknya berarti menolak berkas pendek yang sah.
            if ($next + 2 <= $length
                && ($buffer[$next] !== "\xFF" || (ord($buffer[$next + 1]) & 0xE0) !== 0xE0)
            ) {
                continue;
            }

            return ['offset' => $from + $i] + $frame;
        }

        return null;
    }

    /**
     * @return array{mpeg:int,layer:int,bitrate:int,sampleRate:int,samplesPerFrame:int,frameLength:int,isMono:bool}|null
     */
    private static function parseHeader(string $bytes): ?array
    {
        $b = unpack('C4', $bytes);

        if ($b === false) {
            return null;
        }

        $versionBits = ($b[2] >> 3) & 0x03;
        $layerBits = ($b[2] >> 1) & 0x03;
        $bitrateIndex = ($b[3] >> 4) & 0x0F;
        $sampleRateIndex = ($b[3] >> 2) & 0x03;
        $padding = ($b[3] >> 1) & 0x01;

        // 0b01 pada versi dan 0b00 pada layer keduanya reserved; indeks bitrate
        // 0 berarti "free format" yang durasinya tidak bisa dihitung.
        if ($versionBits === 0b01
            || $layerBits === 0b00
            || $bitrateIndex === 0
            || $bitrateIndex === 0x0F
            || $sampleRateIndex === 0x03
        ) {
            return null;
        }

        $mpeg = match ($versionBits) {
            0b11 => 1,
            0b10 => 2,
            default => 25,
        };

        $layer = match ($layerBits) {
            0b11 => 1,
            0b10 => 2,
            default => 3,
        };

        $bitrate = (self::BITRATES[$mpeg === 1 ? 1 : 2][$layer][$bitrateIndex] ?? 0) * 1000;
        $sampleRate = self::SAMPLE_RATES[$mpeg][$sampleRateIndex] ?? 0;

        if ($bitrate === 0 || $sampleRate === 0) {
            return null;
        }

        $samplesPerFrame = match (true) {
            $layer === 1 => 384,
            $layer === 2 => 1152,
            $mpeg === 1 => 1152,
            default => 576,
        };

        $frameLength = $layer === 1
            ? (int) ((12 * $bitrate / $sampleRate + $padding) * 4)
            : (int) ($samplesPerFrame / 8 * $bitrate / $sampleRate + $padding);

        if ($frameLength <= 4) {
            return null;
        }

        return [
            'mpeg' => $mpeg,
            'layer' => $layer,
            'bitrate' => $bitrate,
            'sampleRate' => $sampleRate,
            'samplesPerFrame' => $samplesPerFrame,
            'frameLength' => $frameLength,
            'isMono' => (($b[4] >> 6) & 0x03) === 0b11,
        ];
    }

    /**
     * Jumlah frame dari header Xing atau VBRI. Berkas VBR menyimpannya di sini
     * justru karena bitrate frame pertama tidak mewakili keseluruhan berkas —
     * memakai bitrate itu pada berkas VBR bisa salah sampai dua kali lipat.
     *
     * @param  resource  $handle
     * @param  array{offset:int,mpeg:int,samplesPerFrame:int,sampleRate:int,isMono:bool}  $frame
     */
    private static function durationFromToc($handle, array $frame): ?float
    {
        $frames = self::xingFrames($handle, $frame) ?? self::vbriFrames($handle, $frame);

        if ($frames === null || $frames <= 0) {
            return null;
        }

        return $frames * $frame['samplesPerFrame'] / $frame['sampleRate'];
    }

    /**
     * @param  resource  $handle
     * @param  array{offset:int,mpeg:int,isMono:bool}  $frame
     */
    private static function xingFrames($handle, array $frame): ?int
    {
        // Xing ditulis setelah side info, yang panjangnya bergantung versi dan
        // jumlah kanal.
        $sideInfo = $frame['mpeg'] === 1
            ? ($frame['isMono'] ? 17 : 32)
            : ($frame['isMono'] ? 9 : 17);

        fseek($handle, $frame['offset'] + 4 + $sideInfo);
        $chunk = fread($handle, 12);

        if (! is_string($chunk) || strlen($chunk) < 12) {
            return null;
        }

        if (! in_array(substr($chunk, 0, 4), ['Xing', 'Info'], true)) {
            return null;
        }

        $flags = unpack('N', substr($chunk, 4, 4));

        if ($flags === false || ($flags[1] & 0x01) === 0) {
            return null;
        }

        $frames = unpack('N', substr($chunk, 8, 4));

        return $frames === false ? null : $frames[1];
    }

    /**
     * @param  resource  $handle
     * @param  array{offset:int}  $frame
     */
    private static function vbriFrames($handle, array $frame): ?int
    {
        fseek($handle, $frame['offset'] + 36);
        $chunk = fread($handle, 22);

        if (! is_string($chunk) || strlen($chunk) < 22 || substr($chunk, 0, 4) !== 'VBRI') {
            return null;
        }

        $frames = unpack('N', substr($chunk, 14, 4));

        return $frames === false ? null : $frames[1];
    }

    /**
     * @param  array{bitrate:int}  $frame
     */
    private static function durationFromBitrate(array $frame, int $audioBytes): ?float
    {
        return $audioBytes <= 0 ? null : $audioBytes * 8 / $frame['bitrate'];
    }
}
