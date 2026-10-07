<?php

namespace App\Support;

use App\Models\InvitationMedia;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Support\Facades\Storage;

/**
 * Melengkapi metadata baris `invitation_media` dari file yang sudah tersimpan.
 * Dipakai observer supaya siapa pun pembuat barisnya — form Filament, seeder,
 * atau job — menghasilkan data yang sama.
 *
 * Lebar dan tinggi hanya dibaca untuk disk lokal. Pada object storage, membuka
 * file berarti satu permintaan jaringan per gambar; pekerjaan itu sudah
 * dilakukan job konversi WebP yang memang harus mengunduh berkasnya. Durasi
 * musik dikecualikan: tidak ada job yang membukanya, jadi dibaca di sini.
 */
final class MediaMetadata
{
    public static function fill(InvitationMedia $media): void
    {
        if (blank($media->path) || blank($media->disk)) {
            return;
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($media->disk);

        if (! $storage->exists($media->path)) {
            return;
        }

        $media->size = $storage->size($media->path);

        // mimeType() bisa mengembalikan false pada driver tertentu.
        $mime = $storage->mimeType($media->path);
        if (is_string($mime) && $mime !== '') {
            $media->mime = $mime;
        }

        if (! $media->collection->isImage()) {
            $media->width = null;
            $media->height = null;
            self::fillDuration($media);

            return;
        }

        self::fillDimensions($media);
    }

    private static function fillDimensions(InvitationMedia $media): void
    {
        if (config("filesystems.disks.{$media->disk}.driver") !== 'local') {
            return;
        }

        /** @var FilesystemAdapter $storage */
        $storage = Storage::disk($media->disk);
        $size = @getimagesize($storage->path($media->path));
        if ($size === false) {
            return;
        }

        [$media->width, $media->height] = $size;
    }

    /**
     * Durasi ikut disalin dari object storage bila perlu. Berbeda dari gambar,
     * tidak ada job yang nanti membuka berkas musik, jadi kalau tidak dibaca
     * sekarang kolomnya tidak akan pernah terisi.
     */
    private static function fillDuration(InvitationMedia $media): void
    {
        /** @var FilesystemAdapter $disk */
        $disk = Storage::disk($media->disk);

        if (config("filesystems.disks.{$media->disk}.driver") === 'local') {
            $media->duration_seconds = AudioDuration::seconds($disk->path($media->path));

            return;
        }

        $temporary = tempnam(sys_get_temp_dir(), 'audio');

        if ($temporary === false) {
            return;
        }

        try {
            $contents = $disk->get($media->path);

            if ($contents === null) {
                return;
            }

            file_put_contents($temporary, $contents);
            $media->duration_seconds = AudioDuration::seconds($temporary);
        } finally {
            @unlink($temporary);
        }
    }
}
