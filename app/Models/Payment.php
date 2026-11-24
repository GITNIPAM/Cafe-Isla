<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = [
        'order_id',
        'method',
        'reference_number',
        'amount',
        'refunded_amount',
        'status',
        'paid_at',
        'refunded_at',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'paid_at' => 'datetime',
            'refunded_at' => 'datetime',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function methodLabel(): string
    {
        return match ($this->method) {
            'gcash' => 'GCash Instant',
            'cod' => 'Cash on Delivery',
            default => strtoupper((string) $this->method),
        };
    }

    public function amountToCollect(): float
    {
        return $this->method === 'cod' && $this->status === 'pending' ? (float) $this->amount : 0.0;
    }

    public function statusLabel(): string
    {
        return match (true) {
            $this->method === 'cod' && $this->status === 'pending' && $this->amountToCollect() === 0.0 => 'No payment due',
            $this->status === 'paid' && (float) $this->refunded_amount > 0 => 'Partially refunded',
            default => ucfirst($this->status),
        };
    }

    public function applyReturnCredit(float $credit): array
    {
        $adjustment = min($credit, $this->amountToCollect());
        $refund = 0.0;

        if ($adjustment > 0) {
            $this->update(['amount' => round((float) $this->amount - $adjustment, 2)]);
        } elseif ($this->status === 'paid' && $this->paid_at) {
            $remaining = max(0.0, round((float) $this->amount - (float) $this->refunded_amount, 2));
            $refund = min($credit, $remaining);
            if ($refund > 0) {
                $refundedAmount = round((float) $this->refunded_amount + $refund, 2);
                $this->update([
                    'refunded_amount' => $refundedAmount,
                    'refunded_at' => now(),
                    'status' => $refundedAmount >= (float) $this->amount ? 'refunded' : 'paid',
                ]);
            }
        }

        return ['refund_amount' => $refund, 'cod_adjustment' => $adjustment];
    }
}
