<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShopVisit extends Model
{
    protected $fillable = [
        'session_token',
        'user_id',
        'route_name',
        'had_cart',
        'converted_order_id',
        'visited_at',
    ];

    protected function casts(): array
    {
        return [
            'had_cart' => 'boolean',
            'visited_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function convertedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'converted_order_id');
    }
}
