<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationGiftConfirmation extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationGiftConfirmationFactory> */
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'sender_name',
        'amount',
        'invitation_bank_account_id',
        'note',
        'proof_path',
        'confirmed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
            'confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /** @return BelongsTo<InvitationBankAccount, $this> */
    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(InvitationBankAccount::class, 'invitation_bank_account_id');
    }

    public function isConfirmed(): bool
    {
        return $this->confirmed_at !== null;
    }
}
