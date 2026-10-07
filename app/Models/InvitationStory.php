<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationStory extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationStoryFactory> */
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'title',
        'happened_on',
        'body',
        'media_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'happened_on' => 'date',
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /** @return BelongsTo<InvitationMedia, $this> */
    public function media(): BelongsTo
    {
        return $this->belongsTo(InvitationMedia::class, 'media_id');
    }

    /** "2019" atau "Maret 2019" — tanggal cerita sengaja tidak sedetail acara. */
    public function dateLabel(): ?string
    {
        return $this->happened_on?->translatedFormat('F Y');
    }
}
