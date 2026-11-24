<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IslandSeries extends Model
{
    protected $table = 'island_series';

    protected $fillable = [
        'code',
        'number_label',
        'name',
        'slug',
        'subtitle',
        'coordinates',
        'terroir_story',
        'accent_hex',
        'hero_image',
        'sort_order',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'island_series_id');
    }
}
