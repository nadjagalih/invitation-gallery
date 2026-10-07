<?php

namespace App\Models;

use App\Enums\AssetState;
use App\Enums\InvitationStatus;
use App\Enums\MediaCollection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invitation extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'template_id',
        'template_version',
        'slug',
        'slug_locked_at',
        'status',
        'asset_state',
        'asset_disk',
        'features',
        'groom_nickname',
        'groom_full_name',
        'groom_child_order',
        'groom_father',
        'groom_mother',
        'groom_instagram',
        'bride_nickname',
        'bride_full_name',
        'bride_child_order',
        'bride_father',
        'bride_mother',
        'bride_instagram',
        'event_date',
        'quote_text',
        'quote_source',
        'opening_words',
        'closing_words',
        'gift_address',
        'moderate_wishes',
        'published_at',
        'expires_at',
        'grace_days',
        'asset_delete_at',
        'expiry_notified_at',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvitationStatus::class,
            'asset_state' => AssetState::class,
            'template_version' => 'integer',
            'features' => 'array',
            'meta' => 'array',
            'event_date' => 'date',
            'moderate_wishes' => 'boolean',
            'grace_days' => 'integer',
            'slug_locked_at' => 'datetime',
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'asset_delete_at' => 'datetime',
            'expiry_notified_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'status' => InvitationStatus::Draft->value,
        'asset_state' => AssetState::Retained->value,
        'template_version' => 1,
        'grace_days' => 30,
        'moderate_wishes' => true,
    ];

    // =====================================================================
    // Relasi
    // =====================================================================

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /** @return HasMany<InvitationEvent, $this> */
    public function events(): HasMany
    {
        return $this->hasMany(InvitationEvent::class)->orderBy('sort_order');
    }

    /** @return HasMany<InvitationStory, $this> */
    public function stories(): HasMany
    {
        return $this->hasMany(InvitationStory::class)->orderBy('sort_order');
    }

    /** @return HasMany<InvitationBankAccount, $this> */
    public function bankAccounts(): HasMany
    {
        return $this->hasMany(InvitationBankAccount::class)->orderBy('sort_order');
    }

    /** @return HasMany<InvitationMedia, $this> */
    public function media(): HasMany
    {
        return $this->hasMany(InvitationMedia::class)->orderBy('sort_order');
    }

    /** @return HasMany<InvitationGiftConfirmation, $this> */
    public function giftConfirmations(): HasMany
    {
        return $this->hasMany(InvitationGiftConfirmation::class);
    }

    /** @return HasMany<Guest, $this> */
    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    /** @return HasMany<Rsvp, $this> */
    public function rsvps(): HasMany
    {
        return $this->hasMany(Rsvp::class);
    }

    /** @return HasMany<Wish, $this> */
    public function wishes(): HasMany
    {
        return $this->hasMany(Wish::class);
    }

    /** @return HasMany<Order, $this> */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /** @return HasMany<InvitationExtension, $this> */
    public function extensions(): HasMany
    {
        return $this->hasMany(InvitationExtension::class);
    }

    // =====================================================================
    // Scope
    // =====================================================================

    public function scopeStatus(Builder $query, InvitationStatus $status): Builder
    {
        return $query->where('status', $status);
    }

    public function scopeAssetState(Builder $query, AssetState $state): Builder
    {
        return $query->where('asset_state', $state);
    }

    // =====================================================================
    // Nama dan tampilan
    // =====================================================================

    public function coupleNames(): string
    {
        return "{$this->groom_nickname} & {$this->bride_nickname}";
    }

    public function coupleFullNames(): string
    {
        return "{$this->groom_full_name} & {$this->bride_full_name}";
    }

    public function title(): string
    {
        return 'The Wedding of '.$this->coupleNames();
    }

    /** "Sabtu, 12 Desember 2026" — dipakai cover dan preview WhatsApp. */
    public function eventDateLabel(): string
    {
        return $this->event_date->translatedFormat('l, j F Y');
    }

    /** "Putra Pertama dari Bapak Suryanto & Ibu Sri Wahyuni" */
    public function groomParentsLine(): string
    {
        return $this->parentsLine('Putra', $this->groom_child_order, $this->groom_father, $this->groom_mother);
    }

    public function brideParentsLine(): string
    {
        return $this->parentsLine('Putri', $this->bride_child_order, $this->bride_father, $this->bride_mother);
    }

    private function parentsLine(string $noun, ?string $childOrder, string $father, string $mother): string
    {
        $prefix = $childOrder ? "{$noun} {$childOrder} dari" : "{$noun} dari";

        return "{$prefix} {$father} & {$mother}";
    }

    /**
     * Acara utama: target countdown dan Save The Date. Jatuh kembali ke acara
     * pertama supaya countdown tidak pernah kosong bila admin lupa menandai.
     */
    public function primaryEvent(): ?InvitationEvent
    {
        $events = $this->relationLoaded('events') ? $this->events : $this->events()->get();

        return $events->firstWhere('is_primary', true) ?? $events->first();
    }

    /** View index untuk versi yang dibekukan pada undangan ini. */
    public function viewName(): string
    {
        return $this->template->viewNamespace($this->template_version).'.index';
    }

    public function publicUrl(?string $guestName = null): string
    {
        $url = route('invitation.show', $this->slug);

        return $guestName ? $url.'?to='.urlencode($guestName) : $url;
    }

    // =====================================================================
    // Fitur
    // =====================================================================

    /**
     * Kontrol fitur per undangan, diturunkan dari `orders.addons` saat pembelian.
     * Template juga harus mampu merender fitur itu — keduanya wajib setuju.
     */
    public function hasFeature(string $feature): bool
    {
        if (! (bool) data_get($this->features ?? [], $feature, false)) {
            return false;
        }

        return $this->template?->supports($feature) ?? false;
    }

    // =====================================================================
    // Media
    // =====================================================================

    /** @return Collection<int, InvitationMedia> */
    public function mediaIn(MediaCollection $collection): Collection
    {
        $media = $this->relationLoaded('media') ? $this->media : $this->media()->get();

        return $media->where('collection', $collection)->values();
    }

    public function firstMediaIn(MediaCollection $collection): ?InvitationMedia
    {
        return $this->mediaIn($collection)->first();
    }

    public function coverUrl(): ?string
    {
        return $this->firstMediaIn(MediaCollection::Cover)?->url(1440);
    }

    public function ogImageUrl(): ?string
    {
        return $this->firstMediaIn(MediaCollection::OgImage)?->url()
            ?? $this->firstMediaIn(MediaCollection::Cover)?->url(960);
    }

    public function musicUrl(): ?string
    {
        return $this->firstMediaIn(MediaCollection::Music)?->url();
    }

    /**
     * Seluruh disk yang benar-benar terpakai undangan ini, digabung dengan
     * tujuan upload saat ini. Cleanup harus menyapu semuanya, bukan satu saja.
     *
     * @return array<int, string>
     */
    public function usedDisks(): array
    {
        return $this->media()
            // reorder() membuang orderBy('sort_order') dari relasi: MySQL menolak
            // DISTINCT yang di-ORDER BY kolom di luar SELECT list.
            ->reorder()
            ->distinct()
            ->pluck('disk')
            ->push($this->asset_disk)
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    public function assetDirectory(): string
    {
        return "invitations/{$this->id}";
    }

    // =====================================================================
    // Slug
    // =====================================================================

    public function isSlugLocked(): bool
    {
        return $this->slug_locked_at !== null;
    }

    /**
     * Dua pasangan bernama Andi dan Sari itu wajar terjadi, jadi generator slug
     * harus menambahkan pembeda sampai benar-benar unik.
     */
    public static function generateSlug(string $groomNickname, string $brideNickname, ?int $ignoreId = null): string
    {
        $base = Str::slug($groomNickname.'-'.$brideNickname) ?: 'undangan';
        $slug = $base;
        $suffix = 1;

        while (static::withTrashed()
            ->where('slug', $slug)
            ->when($ignoreId, fn (Builder $q) => $q->whereKeyNot($ignoreId))
            ->exists()
        ) {
            $slug = $base.'-'.(++$suffix);
        }

        return $slug;
    }
}
