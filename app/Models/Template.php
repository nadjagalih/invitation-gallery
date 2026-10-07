<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Template extends Model
{
    /** @use HasFactory<\Database\Factories\TemplateFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'template_category_id',
        'current_version',
        'price',
        'promo_price',
        'badges',
        'thumbnails',
        'supported_features',
        'demo_invitation_id',
        'description',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'current_version' => 'integer',
            'price' => 'integer',
            'promo_price' => 'integer',
            'badges' => 'array',
            'thumbnails' => 'array',
            'supported_features' => 'array',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<TemplateCategory, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(TemplateCategory::class, 'template_category_id');
    }

    /** @return BelongsTo<Invitation, $this> */
    public function demoInvitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class, 'demo_invitation_id');
    }

    /** @return HasMany<Invitation, $this> */
    public function invitations(): HasMany
    {
        return $this->hasMany(Invitation::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** Harga yang benar-benar dibayar: promo bila ada, kalau tidak harga asli. */
    public function effectivePrice(): int
    {
        return $this->promo_price ?? $this->price;
    }

    public function hasPromo(): bool
    {
        return $this->promo_price !== null && $this->promo_price < $this->price;
    }

    public function discountPercent(): int
    {
        if (! $this->hasPromo() || $this->price === 0) {
            return 0;
        }

        return (int) round((($this->price - $this->promo_price) / $this->price) * 100);
    }

    /** Fitur yang mampu dirender desain ini. */
    public function supports(string $feature): bool
    {
        return in_array($feature, $this->supported_features ?? [], true);
    }

    /** Direktori view untuk versi tertentu, mis. `invitations.elegant-botanical.v1`. */
    public function viewNamespace(int $version): string
    {
        return "invitations.{$this->slug}.v{$version}";
    }
}
