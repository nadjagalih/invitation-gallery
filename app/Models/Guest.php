<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Kosong di MVP. Terisi ketika add-on RSVPKIT dijual.
 */
class Guest extends Model
{
    /** @use HasFactory<\Database\Factories\GuestFactory> */
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'name',
        'phone',
        'token',
        'group_label',
        'quota',
        'opened_at',
    ];

    protected function casts(): array
    {
        return [
            'quota' => 'integer',
            'opened_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
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
}
