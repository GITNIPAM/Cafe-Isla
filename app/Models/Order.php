<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = [
        'order_number',
        'user_id',
        'coupon_id',
        'billing_name',
        'billing_email',
        'billing_phone',
        'shipping_recipient',
        'shipping_phone',
        'shipping_address_line',
        'shipping_city',
        'shipping_province',
        'shipping_postal_code',
        'delivery_zone',
        'subtotal',
        'discount_amount',
        'delivery_fee',
        'grand_total',
        'status',
        'order_notes',
        'placed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'delivery_fee' => 'decimal:2',
            'grand_total' => 'decimal:2',
            'placed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function delivery(): HasOne
    {
        return $this->hasOne(Delivery::class);
    }

    public function returnRequests(): HasMany
    {
        return $this->hasMany(ReturnRequest::class);
    }

    public function emailLogs(): HasMany
    {
        return $this->hasMany(EmailLog::class);
    }

    public function canBeCancelledByCustomer(): bool
    {
        return $this->status === 'pending';
    }

    public function nextCourierStatus(): ?string
    {
        if ($this->status !== 'shipped') {
            return null;
        }

        return match ($this->delivery?->status) {
            'assigned' => 'shipped',
            'in_transit' => 'delivered',
            default => null,
        };
    }

    public function canRequestReturnByCustomer(): bool
    {
        return $this->isReturnable() && $this->returnRequests->isEmpty();
    }

    public function isReturnable(): bool
    {
        return in_array($this->status, ['delivered', 'shipped'], true);
    }

    public function returnCredit(?OrderItem $item): float
    {
        if (! $item) {
            return (float) $this->grand_total;
        }

        $lineTotal = (float) $item->line_total;
        $subtotal = (float) $this->subtotal;
        $discountShare = $subtotal > 0 ? round((float) $this->discount_amount * $lineTotal / $subtotal, 2) : 0.0;

        return max(0.0, round($lineTotal - $discountShare, 2));
    }
}
