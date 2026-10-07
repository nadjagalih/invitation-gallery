<?php

namespace App\Filament\Forms\Components;

use App\Enums\MediaCollection;
use App\Models\Invitation;
use App\Rules\AudioMaxDuration;
use App\Support\MediaSync;
use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Field unggah yang menulis ke `invitation_media`, bukan ke kolom `invitations`.
 *
 * Semua keputusan yang harus konsisten di seluruh panel dikumpulkan di sini:
 * disk tujuan, direktori per undangan, nama berbasis hash, dan validasi mime
 * lewat isi berkas. Menyalinnya per field berarti cepat atau lambat ada satu
 * field yang lupa salah satunya.
 *
 * Field ini hanya muncul pada halaman Edit. Direktori tujuannya mengandung id
 * undangan, dan id itu belum ada saat form Create dikirim.
 */
final class MediaUpload
{
    /**
     * Nama field bersifat virtual — tidak ada kolom `media_cover` di tabel
     * invitations — jadi `dehydrated(false)` wajib menyertainya.
     */
    public static function fieldName(MediaCollection $collection): string
    {
        return 'media_'.$collection->value;
    }

    /** Tujuan upload baru. File lama tetap dilayani dari disknya sendiri. */
    public static function diskFor(?Invitation $invitation): string
    {
        return $invitation?->asset_disk ?: (string) config('invitation.asset_disk');
    }

    public static function image(MediaCollection $collection): FileUpload
    {
        return self::imageConstraints(self::base($collection));
    }

    public static function gallery(): FileUpload
    {
        return self::image(MediaCollection::Gallery)
            ->multiple()
            ->reorderable()
            // Tanpa ini, memilih file lagi akan mengganti seluruh galeri alih-alih
            // menambah — perilaku yang membuat admin kehilangan pekerjaannya.
            ->appendFiles()
            ->panelLayout('grid')
            ->maxFiles(24)
            ->helperText('Tarik untuk mengubah urutan tampil. Maksimal 24 foto.');
    }

    public static function music(): FileUpload
    {
        $maxKb = (int) config('invitation.upload.music_max_kb');
        $maxSeconds = (int) config('invitation.upload.music_max_seconds');

        return self::base(MediaCollection::Music)
            ->acceptedFileTypes(config('invitation.upload.music_mimes'))
            ->maxSize($maxKb)
            ->rules([new AudioMaxDuration($maxSeconds)])
            ->helperText(sprintf(
                'MP3, maksimal %s MB dan %d menit. Musik hanya berputar setelah tamu menekan "Buka Undangan" — browser memblokir suara sebelum ada sentuhan.',
                round($maxKb / 1024, 1),
                intdiv($maxSeconds, 60),
            ));
    }

    /**
     * Gambar milik satu baris repeater — foto cerita, logo bank. Berbeda dari
     * field collection di atas, path-nya ikut didehidrasi ke state baris supaya
     * mutator repeater bisa menukarnya menjadi id media.
     *
     * @see MediaRowUpload
     */
    public static function rowImage(MediaCollection $collection, ?string $label = null): FileUpload
    {
        return self::imageConstraints(
            self::storage($collection, MediaRowUpload::FIELD)
                ->label($label ?? $collection->label())
                // Direktorinya mengandung id undangan, dan pada halaman Create id
                // itu belum ada.
                ->visibleOn('edit'),
        )
            ->imagePreviewHeight('96')
            ->helperText(null);
    }

    private static function base(MediaCollection $collection): FileUpload
    {
        $isMultiple = $collection === MediaCollection::Gallery;

        return self::storage($collection, self::fieldName($collection))
            ->label($collection->label())
            // Kolomnya tidak ada di tabel invitations; barisnya ditulis
            // saveRelationshipsUsing() di bawah.
            ->dehydrated(false)
            ->afterStateHydrated(function (FileUpload $component) use ($collection, $isMultiple): void {
                $record = self::invitationOf($component);

                $component->state(match (true) {
                    $record === null => $isMultiple ? [] : null,
                    $isMultiple => MediaSync::paths($record, $collection),
                    default => MediaSync::path($record, $collection),
                });

                // afterStateHydrated() menggantikan callback bawaan FileUpload,
                // bukan menambahnya. hydrateFiles() harus dipanggil ulang agar
                // path yang berkasnya sudah hilang dari disk tidak tampil
                // sebagai kartu rusak yang tidak bisa dihapus.
                $component->hydrateFiles();
            })
            ->saveRelationshipsUsing(function (FileUpload $component, Invitation $record) use ($collection, $isMultiple): void {
                $state = $component->getState();

                $isMultiple
                    ? MediaSync::many($record, $collection, is_array($state) ? $state : [], $component->getDiskName())
                    : MediaSync::single($record, $collection, is_string($state) ? $state : null, $component->getDiskName());
            });
    }

    /**
     * Bagian yang sama untuk setiap unggahan: ke mana berkas ditulis, dengan
     * nama apa, dan path mana yang boleh dikirim balik browser.
     */
    private static function storage(MediaCollection $collection, string $name): FileUpload
    {
        return FileUpload::make($name)
            ->visibility('public')
            ->downloadable()
            ->openable()
            ->disk(fn (FileUpload $component): string => self::diskFor(self::invitationOf($component)))
            ->directory(function (FileUpload $component) use ($collection): ?string {
                $invitation = self::invitationOf($component);

                return $invitation
                    ? $invitation->assetDirectory().'/'.$collection->directory()
                    : null;
            })
            ->getUploadedFileNameForStorageUsing(self::hashedFileName())
            // Path yang dikirim balik dari browser dibatasi pada berkas yang
            // memang tercatat sebagai milik undangan ini. Tanpa ini sebuah path
            // hasil suntingan bisa dipasang sebagai media, atau dihapus.
            ->preventFilePathTampering(
                allowFilePathUsing: fn (FileUpload $component, string $file): bool => self::invitationOf($component)
                    ?->media()
                    ->where('path', $file)
                    ->exists() ?? false,
            );
    }

    private static function imageConstraints(FileUpload $field): FileUpload
    {
        $minWidth = (int) config('invitation.upload.image_min_width');
        $minHeight = (int) config('invitation.upload.image_min_height');
        $maxKb = (int) config('invitation.upload.image_max_kb');

        return $field
            ->acceptedFileTypes(config('invitation.upload.image_mimes'))
            ->maxSize($maxKb)
            ->rules(["dimensions:min_width={$minWidth},min_height={$minHeight}"])
            ->imagePreviewHeight('160')
            ->helperText(sprintf(
                'JPG, PNG, atau WebP. Minimal %d×%d piksel, maksimal %s MB. Versi WebP dan ukuran turunannya dibuat otomatis setelah disimpan.',
                $minWidth,
                $minHeight,
                round($maxKb / 1024, 1),
            ));
    }

    /**
     * Undangan pemilik berkas diambil dari halaman, bukan dari record komponen.
     * Field di dalam repeater punya record sendiri — baris cerita atau rekening —
     * sehingga `$record` di sana bukan undangan yang dicari.
     *
     * Pada halaman Create nilainya null sampai record terbentuk, dan itu memang
     * alasan setiap field media hanya tampil di halaman Edit.
     */
    public static function invitationFrom(mixed $livewire): ?Invitation
    {
        $record = is_object($livewire) && method_exists($livewire, 'getRecord')
            ? $livewire->getRecord()
            : null;

        return $record instanceof Invitation ? $record : null;
    }

    private static function invitationOf(FileUpload $component): ?Invitation
    {
        return self::invitationFrom($component->getLivewire());
    }

    /**
     * Nama berbasis hash isi berkas: unggahan yang sama tidak pernah menumpuk
     * dua kali, dan karena nama tidak pernah berubah untuk isi yang sama, URL-nya
     * aman di-cache selamanya. Ekstensi diturunkan dari mime hasil deteksi isi,
     * bukan dari nama yang dikirim browser.
     */
    private static function hashedFileName(): Closure
    {
        return static function (TemporaryUploadedFile $file): string {
            $hash = @hash_file('sha256', $file->getRealPath());
            $name = is_string($hash) ? substr($hash, 0, 32) : (string) Str::ulid();

            return $name.'.'.self::extensionFor($file);
        };
    }

    private static function extensionFor(TemporaryUploadedFile $file): string
    {
        return match ($file->getMimeType()) {
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'audio/mpeg', 'audio/mp3' => 'mp3',
            default => $file->guessExtension() ?: 'bin',
        };
    }
}
