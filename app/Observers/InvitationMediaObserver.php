<?php

namespace App\Observers;

use App\Models\InvitationMedia;
use App\Support\MediaMetadata;
use Illuminate\Support\Facades\Storage;

class InvitationMediaObserver
{
    /**
     * Metadata diisi di observer, bukan di controller atau form, supaya baris
     * yang dibuat seeder dan job juga lengkap. Hanya dijalankan saat `path`
     * berubah: menghitung ulang pada tiap penyimpanan berarti satu pembacaan
     * file untuk perubahan `sort_order` sekalipun.
     */
    public function saving(InvitationMedia $media): void
    {
        if ($media->isDirty('path') || $media->isDirty('disk')) {
            MediaMetadata::fill($media);
        }
    }

    /**
     * File lama dihapus saat barisnya hilang. Tanpa ini, mengganti foto cover
     * sepuluh kali menyisakan sembilan berkas yang tidak pernah tersentuh lagi
     * dan tetap ikut terhitung pada tagihan penyimpanan.
     */
    public function deleted(InvitationMedia $media): void
    {
        $this->deleteFiles($media, $media->path, $media->variants ?? []);
    }

    /** Path lama dibuang begitu baris menunjuk berkas baru. */
    public function updated(InvitationMedia $media): void
    {
        if (! $media->wasChanged('path')) {
            return;
        }

        $previousPath = $media->getOriginal('path');
        $previousVariants = $media->getOriginal('variants');

        if (is_string($previousVariants)) {
            $previousVariants = json_decode($previousVariants, true) ?: [];
        }

        $this->deleteFiles(
            $media,
            is_string($previousPath) ? $previousPath : null,
            is_array($previousVariants) ? $previousVariants : [],
            keep: [$media->path],
        );
    }

    /**
     * @param  array<string, string>  $variants
     * @param  array<int, string|null>  $keep
     */
    private function deleteFiles(InvitationMedia $media, ?string $path, array $variants, array $keep = []): void
    {
        $disk = $media->getOriginal('disk') ?: $media->disk;

        if (blank($disk)) {
            return;
        }

        $paths = collect([$path])
            ->merge(array_values($variants))
            ->filter(fn ($candidate) => is_string($candidate) && $candidate !== '')
            ->reject(fn (string $candidate) => in_array($candidate, $keep, true))
            ->unique()
            ->all();

        if ($paths === []) {
            return;
        }

        Storage::disk($disk)->delete($paths);
    }
}
