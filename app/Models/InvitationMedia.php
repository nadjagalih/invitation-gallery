<?php

namespace App\Models;

use App\Enums\MediaCollection;
use App\Observers\InvitationMediaObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[ObservedBy(InvitationMediaObserver::class)]
class InvitationMedia extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationMediaFactory> */
    use HasFactory;

    protected $table = 'invitation_media';

    protected $fillable = [
        'invitation_id',
        'collection',
        'disk',
        'path',
        'mime',
        'size',
        'width',
        'height',
        'duration_seconds',
        'variants',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'collection' => MediaCollection::class,
            'variants' => 'array',
            'size' => 'integer',
            'width' => 'integer',
            'height' => 'integer',
            'duration_seconds' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /**
     * URL file ini, di-resolve lewat disk-nya sendiri. Inilah sebabnya `disk`
     * disimpan per baris: asset lama yang diunggah saat FILESYSTEM_DISK=local
     * tetap terbuka setelah default berpindah ke r2.
     *
     * @param  int|null  $width  Ambil derivative terdekat >= lebar ini bila tersedia.
     */
    public function url(?int $width = null): string
    {
        return Storage::disk($this->disk)->url($this->variantPath($width));
    }

    /** Path derivative terkecil yang masih >= $width, atau original bila tidak ada. */
    public function variantPath(?int $width = null): string
    {
        $variants = $this->variants ?? [];

        if ($width === null || $variants === []) {
            return $variants['original'] ?? $this->path;
        }

        $candidates = collect($variants)
            ->except('original')
            ->mapWithKeys(fn ($path, $key) => [(int) $key => $path])
            ->filter(fn ($path, $key) => $key > 0)
            ->sortKeys();

        $match = $candidates->keys()->first(fn (int $key) => $key >= $width)
            ?? $candidates->keys()->last();

        return $match !== null
            ? $candidates[$match]
            : ($variants['original'] ?? $this->path);
    }

    /**
     * Atribut srcset untuk <img>, kosong bila derivative belum dibuat job.
     */
    public function srcset(): ?string
    {
        $variants = collect($this->variants ?? [])
            ->except('original')
            ->mapWithKeys(fn ($path, $key) => [(int) $key => $path])
            ->filter(fn ($path, $key) => $key > 0)
            ->sortKeys();

        if ($variants->isEmpty()) {
            return null;
        }

        $disk = Storage::disk($this->disk);

        return $variants
            ->map(fn (string $path, int $width) => $disk->url($path)." {$width}w")
            ->implode(', ');
    }

    public function isProcessed(): bool
    {
        return ! empty($this->variants);
    }
}
