<?php

namespace App\Support;

use App\Enums\MediaCollection;
use App\Models\Invitation;
use App\Models\InvitationMedia;
use Illuminate\Support\Facades\DB;

/**
 * Menyelaraskan baris `invitation_media` dengan daftar path yang tersimpan di
 * disk.
 *
 * FileUpload Filament hanya mengurus berkasnya: ia menyimpan file lalu
 * menyerahkan path. Yang menerjemahkan path itu menjadi baris — dan menghapus
 * baris yang tidak lagi ada di daftar — adalah kelas ini, supaya keputusan
 * "berapa baris untuk satu collection" tidak tersebar ke tiap field form.
 *
 * Berkas fisiknya tidak pernah dihapus di sini. Itu tugas observer, yang
 * mengikuti barisnya sehingga penghapusan lewat panel, artisan, atau cleanup
 * memberi hasil yang sama.
 */
final class MediaSync
{
    /** Collection satu berkas: cover, foto mempelai, gambar OG, musik. */
    public static function single(
        Invitation $invitation,
        MediaCollection $collection,
        ?string $path,
        string $disk,
    ): void {
        self::many($invitation, $collection, $path === null ? [] : [$path], $disk);
    }

    /**
     * Collection banyak berkas. Urutan `$paths` menjadi `sort_order`, jadi
     * mengurutkan ulang di panel cukup menulis kolom itu tanpa menyentuh file.
     *
     * @param  array<int, string>  $paths
     */
    public static function many(
        Invitation $invitation,
        MediaCollection $collection,
        array $paths,
        string $disk,
    ): void {
        $paths = array_values(array_unique(array_filter($paths, fn ($path) => is_string($path) && $path !== '')));

        DB::transaction(function () use ($invitation, $collection, $paths, $disk) {
            $existing = $invitation->media()
                ->where('collection', $collection)
                ->get()
                ->keyBy('path');

            // Dihapus satu per satu, bukan lewat delete() massal: observer yang
            // membuang berkasnya hanya dipanggil pada penghapusan per model.
            $existing
                ->reject(fn (InvitationMedia $media) => in_array($media->path, $paths, true))
                ->each(fn (InvitationMedia $media) => $media->delete());

            foreach ($paths as $order => $path) {
                $media = $existing->get($path);

                if ($media === null) {
                    $invitation->media()->create([
                        'collection' => $collection,
                        'disk' => $disk,
                        'path' => $path,
                        'sort_order' => $order,
                    ]);

                    continue;
                }

                // Baris yang tidak berubah tidak menghasilkan query: Eloquent
                // melewati update ketika tidak ada atribut yang kotor, dan
                // observer hanya membaca ulang file bila `path` atau `disk`
                // yang berubah.
                $media->fill(['sort_order' => $order, 'disk' => $disk])->save();
            }
        });

        $invitation->unsetRelation('media');
    }

    /** Path yang tersimpan untuk collection satu berkas, untuk mengisi form. */
    public static function path(Invitation $invitation, MediaCollection $collection): ?string
    {
        return $invitation->firstMediaIn($collection)?->path;
    }

    /**
     * @return array<int, string>
     */
    public static function paths(Invitation $invitation, MediaCollection $collection): array
    {
        return $invitation->mediaIn($collection)->pluck('path')->all();
    }
}
