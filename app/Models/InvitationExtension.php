<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationExtension extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationExtensionFactory> */
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'order_id',
        'previous_expires_at',
        'new_expires_at',
        'days',
    ];

    protected function casts(): array
    {
        return [
            'previous_expires_at' => 'datetime',
            'new_expires_at' => 'datetime',
            'days' => 'integer',
        ];
    }

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /** @return BelongsTo<Order, $this> */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
