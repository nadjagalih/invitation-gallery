<?php

namespace App\Models;

use App\Enums\Attendance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Rsvp extends Model
{
    /** @use HasFactory<\Database\Factories\RsvpFactory> */
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'guest_id',
        'name',
        'attendance',
        'party_size',
        'message',
        'ip_hash',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'attendance' => Attendance::class,
            'party_size' => 'integer',
        ];
    }

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /** @return BelongsTo<Guest, $this> */
    public function guest(): BelongsTo
    {
        return $this->belongsTo(Guest::class);
    }
}
