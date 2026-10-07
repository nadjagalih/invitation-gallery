<?php

namespace App\Support;

use App\Enums\MediaCollection;
use App\Models\Invitation;
use App\Models\InvitationMedia;

/**
 * Media yang dimiliki satu baris repeater: foto cerita dan logo bank. Berbeda
 * dari MediaSync yang mengurus satu collection sebagai satu daftar, di sini
 * satu berkas menempel pada satu baris lewat kolom foreign key.
 *
 * Urutan penyimpanan Filament memaksa pembagian dua tahap. Mutator repeater
 * berjalan per baris, dan saat baris pertama diproses baris lain belum tentu
 * sudah tersimpan — jadi mutator hanya boleh membuat baris media dan
 * mengembalikan idnya, tidak boleh menghapus apa pun. Penghapusan baris media
 * yang sudah tidak ditunjuk siapa pun dikerjakan prune() setelah seluruh
 * repeater selesai, karena hanya di titik itu daftar penunjuknya lengkap.
 */
final class MediaAttachment
{
    /** Mengembalikan id baris media untuk sebuah path, membuatnya bila perlu. */
    public static function claim(
        Invitation $invitation,
        MediaCollection $collection,
        mixed $path,
        string $disk,
    ): ?int {
        if (! is_string($path) || $path === '') {
            return null;
        }

        // Nama berkas berbasis hash isinya, jadi dua baris yang memakai gambar
        // sama menghasilkan path sama. firstOrCreate() menjaga keduanya menunjuk
        // satu baris media alih-alih menduplikasinya.
        return $invitation->media()
            ->firstOrCreate(
                ['collection' => $collection, 'path' => $path],
                ['disk' => $disk],
            )
            ->getKey();
    }

    /**
     * Membuang baris media dalam satu collection yang tidak ada di daftar
     * penunjuk. Daftar kosong berarti tidak ada yang menunjuk sama sekali,
     * sehingga seluruh collection itu memang harus habis.
     *
     * @param  array<int, int>  $referencedIds
     */
    public static function prune(Invitation $invitation, MediaCollection $collection, array $referencedIds): void
    {
        $invitation->media()
            ->where('collection', $collection)
            ->when($referencedIds !== [], fn ($query) => $query->whereKeyNot($referencedIds))
            ->get()
            // Dihapus satu per satu, bukan lewat delete() massal: observer yang
            // membuang berkas fisiknya hanya dipanggil pada penghapusan per model.
            ->each(fn (InvitationMedia $media) => $media->delete());

        $invitation->unsetRelation('media');
    }

    /**
     * Membersihkan kedua collection per-baris sekaligus. Dipanggil dari hook
     * afterSave halaman panel, sesudah repeater menuliskan barisnya.
     */
    public static function pruneRowCollections(Invitation $invitation): void
    {
        self::prune(
            $invitation,
            MediaCollection::Story,
            $invitation->stories()->pluck('media_id')->filter()->values()->all(),
        );

        self::prune(
            $invitation,
            MediaCollection::BankLogo,
            $invitation->bankAccounts()->pluck('logo_media_id')->filter()->values()->all(),
        );
    }

    /** Path berkas untuk mengisi field unggah dari id yang tersimpan. */
    public static function pathFor(mixed $mediaId): ?string
    {
        if (blank($mediaId)) {
            return null;
        }

        $path = InvitationMedia::query()->whereKey($mediaId)->value('path');

        return is_string($path) ? $path : null;
    }
}
