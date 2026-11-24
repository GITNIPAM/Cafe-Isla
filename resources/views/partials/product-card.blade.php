@php
    $activeVariants = $product->variants->where('is_active', true);
    $firstAvailable = $activeVariants->first(fn ($v) => $v->stock_qty > 0) ?? $activeVariants->first();
    $totalStock = $activeVariants->sum('stock_qty');
    $lowBatch = $activeVariants->contains(fn ($v) => $v->stock_qty > 0 && $v->stock_qty <= $v->low_stock_threshold);
@endphp
<article class="product-card">
    <a href="{{ route('product.show', $product->slug) }}" class="product-card__media">
        <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}">
        <span class="product-card__stamp product-card__stamp--island" style="background-color: {{ $product->islandSeries->accent_hex }};">
            {{ str_replace(' Series', '', $product->islandSeries->name) }}
        </span>
        @if($totalStock <= 0)
            <span class="product-card__stamp product-card__stamp--sold">Sold Out</span>
        @elseif($lowBatch)
            <span class="product-card__stamp">Low Batch</span>
        @endif
    </a>

    <div class="product-card__body">
        <h3 class="product-card__title">
            <a href="{{ route('product.show', $product->slug) }}">{{ $product->name }}</a>
        </h3>
        <p class="product-card__excerpt">{{ $product->short_excerpt }}</p>

        <div class="product-card__footer">
            <span class="product-card__price tabular">₱{{ number_format((float) ($firstAvailable->price ?? $product->base_price), 0) }}</span>
            @if($firstAvailable && $firstAvailable->stock_qty > 0)
                <button
                    type="button"
                    class="tag-btn tag-btn--sm"
                    data-quick-pour="{{ $firstAvailable->id }}"
                >
                    {{ $product->is_perishable ? 'Pour to Bag' : 'Add Merch' }}
                </button>
            @else
                <a href="{{ route('product.show', $product->slug) }}" class="tag-btn tag-btn--outline tag-btn--sm">Details</a>
            @endif
        </div>
    </div>
</article>
