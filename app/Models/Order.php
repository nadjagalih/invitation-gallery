<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentChannel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Order extends Model
{
    /** @use HasFactory<\Database\Factories\OrderFactory> */
    use HasFactory;

    protected $fillable = [
        'order_number',
        'user_id',
        'invitation_id',
        'template_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'base_price',
        'addons',
        'discount',
        'total',
        'status',
        'payment_channel',
        'payment_ref',
        'paid_at',
        'proof_path',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_channel' => PaymentChannel::class,
            'addons' => 'array',
            'base_price' => 'integer',
            'discount' => 'integer',
            'total' => 'integer',
            'paid_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'status' => OrderStatus::Pending->value,
        'payment_channel' => PaymentChannel::ManualTransfer->value,
        'discount' => 0,
    ];

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return BelongsTo<Invitation, $this> */
    public function invitation(): BelongsTo
    {
        return $this->belongsTo(Invitation::class);
    }

    /** @return BelongsTo<Template, $this> */
    public function template(): BelongsTo
    {
        return $this->belongsTo(Template::class);
    }

    /** @return HasMany<InvitationExtension, $this> */
    public function extensions(): HasMany
    {
        return $this->hasMany(InvitationExtension::class);
    }

    public static function generateOrderNumber(): string
    {
        return 'INV-'.now()->format('Ymd').'-'.Str::upper(Str::random(6));
    }

    /**
     * Fitur yang dibeli, diturunkan menjadi `invitations.features`. Harga add-on
     * disimpan apa adanya pada order sehingga perubahan harga di kemudian hari
     * tidak mengubah hak fitur pelanggan lama.
     *
     * @return array<string, bool>
     */
    public function purchasedFeatures(): array
    {
        return collect($this->addons ?? [])
            ->mapWithKeys(fn (array $addon, string $key) => [$key => true])
            ->all();
    }

    public function addonsTotal(): int
    {
        return (int) collect($this->addons ?? [])->sum('price');
    }

    public function isPaid(): bool
    {
        return $this->status->isPaid();
    }
}
