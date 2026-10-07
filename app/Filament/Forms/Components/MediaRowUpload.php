<?php

namespace App\Filament\Forms\Components;

use App\Enums\MediaCollection;
use App\Support\MediaAttachment;
use Filament\Forms\Components\Repeater;

/**
 * Menyambungkan satu gambar per baris repeater ke `invitation_media`.
 *
 * Repeater Filament hanya memanggil saveRelationships() pada baris yang baru
 * dibuat, tidak pada baris yang diperbarui, jadi FileUpload di dalam repeater
 * tidak bisa memakai saveRelationshipsUsing() seperti field media lainnya.
 * Yang bisa diandalkan: saveUploadedFiles() dijalankan oleh
 * callBeforeStateDehydrated() di tingkat form dan itu menelusuri seluruh child
 * schema, termasuk isi repeater. Ketika mutator repeater berjalan, state field
 * unggah sudah berupa path final di disk — mutator hanya perlu menukarnya
 * menjadi id baris media.
 *
 * Baris media yang tidak lagi ditunjuk siapa pun dibersihkan setelahnya oleh
 * MediaAttachment::pruneRowCollections() dari hook afterSave halaman.
 */
final class MediaRowUpload
{
    /** Field virtual; tidak ada kolomnya di tabel baris repeater. */
    public const FIELD = 'media_path';

    public static function attachTo(Repeater $repeater, MediaCollection $collection, string $column): Repeater
    {
        $toMediaId = function (array $data) use ($repeater, $collection, $column): array {
            // Field unggahnya hanya tampil di halaman Edit. Pada Create kuncinya
            // tidak ada sama sekali, dan kolom media harus dibiarkan apa adanya.
            if (! array_key_exists(self::FIELD, $data)) {
                return $data;
            }

            $path = $data[self::FIELD];
            unset($data[self::FIELD]);

            $invitation = MediaUpload::invitationFrom($repeater->getLivewire());

            if ($invitation === null) {
                return $data;
            }

            $data[$column] = MediaAttachment::claim(
                $invitation,
                $collection,
                $path,
                MediaUpload::diskFor($invitation),
            );

            return $data;
        };

        return $repeater
            ->mutateRelationshipDataBeforeFillUsing(fn (array $data): array => [
                ...$data,
                self::FIELD => MediaAttachment::pathFor($data[$column] ?? null),
            ])
            ->mutateRelationshipDataBeforeCreateUsing($toMediaId)
            ->mutateRelationshipDataBeforeSaveUsing($toMediaId);
    }
}
