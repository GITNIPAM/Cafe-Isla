@extends('layouts.app')

@section('title', $product->name . ' · Café Isla')

@section('content')
    <section class="container">
        <div style="padding-top:1.5rem;font-family:var(--font-mono);font-size:0.8rem;color:var(--taupe-muted);">
            <a href="{{ route('home') }}">Home</a> /
            <a href="{{ route('menu.index', ['island' => $product->islandSeries->slug]) }}">{{ $product->islandSeries->name }}</a> /
            <span>{{ $product->name }}</span>
        </div>

        <div class="product-detail-wrap">
            <div class="product-detail__photo">
                <img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}">
            </div>

            <div>
                <div style="display:flex;gap:0.65rem;flex-wrap:wrap;align-items:center;">
                    <span class="status-tag" style="background:{{ $product->islandSeries->accent_hex }};color:#FBF8F1;border-color:{{ $product->islandSeries->accent_hex }};">
                        {{ $product->islandSeries->name }} &middot; {{ $product->islandSeries->coordinates }}
                    </span>
                    <span class="status-tag">
                        {{ $product->is_perishable ? 'Fresh Perishable Pour (Local Express)' : 'Isla Merch (Nationwide Parcel)' }}
                    </span>
                </div>

                <h1 class="product-detail__title">{{ $product->name }}</h1>
                <p style="margin:0.75rem 0 0 0;font-family:var(--font-mono);font-size:0.88rem;color:var(--taupe-muted);">
                    SKU {{ $product->sku }} &middot; {{ $product->category->name }}
                </p>

                <p style="margin:1.25rem 0;max-width:62ch;font-size:1.04rem;">
                    {{ $product->description }}
                </p>

                @if($product->tasting_notes)
                    <div style="border-top:1px solid var(--espresso-ink);border-bottom:1px solid var(--espresso-ink);padding:0.8rem 0;margin-bottom:1.25rem;font-family:var(--font-mono);font-size:0.84rem;">
                        <strong>{{ $product->is_perishable ? 'Tasting Notes:' : 'Material & Fit:' }}</strong>
                        {{ $product->tasting_notes }}
                    </div>
                @endif

                <form method="POST" action="{{ route('cart.items.add') }}" class="form-stack">
                    @csrf
                    <div>
                        <span class="field-label">Select Variant &amp; Batch Availability</span>
                        <div class="variant-selector-grid">
                            @foreach($product->variants as $idx => $variant)
                                <label class="variant-chip">
                                    <input
                                        type="radio"
                                        name="variant_id"
                                        value="{{ $variant->id }}"
                                        @checked($idx === 0 || ($product->variants->first()->stock_qty === 0 && $variant->stock_qty > 0))
                                        @disabled($variant->stock_qty < 1)
                                    >
                                    <span class="tabular">
                                        {{ $variant->label }} &middot; ₱{{ number_format((float) $variant->price, 0) }}
                                        @if($variant->stock_qty < 1)
                                            <em>(Sold Out)</em>
                                        @elseif($variant->isLowStock())
                                            <em style="color:var(--clay-coral);">({{ $variant->stock_qty }} left)</em>
                                        @endif
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </div>

                    <div class="form-row-2">
                        <div class="field-group">
                            <label for="quantity" class="field-label">Quantity</label>
                            <input type="number" id="quantity" name="quantity" value="1" min="1" max="15" class="field-control tabular">
                        </div>
                        <div class="field-group">
                            <label for="custom_notes" class="field-label">
                                {{ $product->is_perishable ? 'Barista Sweetness / Ice Note' : 'Gift or Sizing Note' }}
                            </label>
                            <input
                                type="text"
                                id="custom_notes"
                                name="custom_notes"
                                placeholder="{{ $product->is_perishable ? 'e.g. 50% sweet, light ice' : 'Optional note' }}"
                                class="field-control"
                            >
                        </div>
                    </div>

                    <div>
                        <button type="submit" class="tag-btn tag-btn--canopy" @disabled($product->totalStock() < 1)>
                            {{ $product->totalStock() > 0 ? 'Add to Shopping Bag' : 'Batch Currently Sold Out' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>

        @if($related->isNotEmpty())
            <section style="padding-block:2.5rem 4rem;border-top:1px solid var(--espresso-ink);">
                <h2 class="section-heading" style="margin-bottom:1.5rem;">More from {{ $product->islandSeries->name }}</h2>
                <div class="catalog-grid" style="padding-block:0;">
                    @foreach($related as $item)
                        @include('partials.product-card', ['product' => $item])
                    @endforeach
                </div>
            </section>
        @endif
    </section>
@endsection
