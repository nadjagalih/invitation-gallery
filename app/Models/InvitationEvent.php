<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvitationEvent extends Model
{
    /** @use HasFactory<\Database\Factories\InvitationEventFactory> */
    use HasFactory;

    protected $fillable = [
        'invitation_id',
        'title',
        'starts_at',
        'ends_at',
        'timezone',
        'venue_name',
        'address',
        'maps_url',
        'notes',
        'is_primary',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_primary' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    protected $attributes = [
        'timezone' => 'Asia/Jakarta',
        'is_primary' => false,
    ];

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /** Waktu mulai pada zona waktu acara, bukan zona waktu server. */
    public function localStart(): CarbonInterface
    {
        return $this->starts_at->copy()->setTimezone($this->timezone);
    }

    public function localEnd(): ?CarbonInterface
    {
        return $this->ends_at?->copy()->setTimezone($this->timezone);
    }

    public function dateLabel(): string
    {
        return $this->localStart()->translatedFormat('l, j F Y');
    }

    /**
     * "08.00 WIB - Selesai" atau "11.00 - 14.00 WIB". Singkatan zona waktu
     * dicetak eksplisit karena tamu bisa membaca dari zona waktu berbeda.
     */
    public function timeLabel(): string
    {
        $abbr = $this->timezoneAbbreviation();
        $start = $this->localStart()->format('H.i');

        if (! $this->ends_at) {
            return "{$start} {$abbr} - Selesai";
        }

        return "{$start} - {$this->localEnd()->format('H.i')} {$abbr}";
    }

    public function timezoneAbbreviation(): string
    {
        return match ($this->timezone) {
            'Asia/Jakarta' => 'WIB',
            'Asia/Makassar' => 'WITA',
            'Asia/Jayapura' => 'WIT',
            default => $this->localStart()->format('T'),
        };
    }
}
