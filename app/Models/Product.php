<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'island_series_id',
        'category_id',
        'sku',
        'name',
        'slug',
        'short_excerpt',
        'description',
        'tasting_notes',
        'is_perishable',
        'base_price',
        'image_path',
        'is_featured',
        'is_archived',
    ];

    protected function casts(): array
    {
        return [
            'is_perishable' => 'boolean',
            'base_price' => 'decimal:2',
            'is_featured' => 'boolean',
            'is_archived' => 'boolean',
        ];
    }

    public function islandSeries(): BelongsTo
    {
        return $this->belongsTo(IslandSeries::class, 'island_series_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function totalStock(): int
    {
        return (int) $this->variants->where('is_active', true)->sum('stock_qty');
    }

    public function hasLowStock(): bool
    {
        return $this->variants
            ->where('is_active', true)
            ->contains(fn (ProductVariant $v) => $v->stock_qty > 0 && $v->stock_qty <= $v->low_stock_threshold);
    }

    public function imageUrl(): string
    {
        if (! $this->image_path) {
            return asset('assets/images/editorial/menu-table-ritual.jpg');
        }

        if (str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }

        return asset(ltrim($this->image_path, '/'));
    }
}
