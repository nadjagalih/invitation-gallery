<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvitationBankAccount extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationBankAccountFactory> */
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'bank_name',
        'account_number',
        'account_holder',
        'logo_media_id',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /** @return BelongsTo<InvitationMedia, $this> */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(InvitationMedia::class, 'logo_media_id');
    }

    /** @return HasMany<InvitationGiftConfirmation, $this> */
    public function giftConfirmations(): HasMany
    {
        return $this->hasMany(InvitationGiftConfirmation::class);
    }
}
