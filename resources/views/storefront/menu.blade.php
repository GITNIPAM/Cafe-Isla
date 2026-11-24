@extends('layouts.app')

@section('title', 'Menu & Isla Merch · Café Isla')

@section('content')
    <section class="page-intro">
        <div class="container">
            <h1 class="page-intro__title">Menu &amp; Coastal Goods</h1>
            <p class="page-intro__lead">
                Search or dynamically filter all 17 island beverages and 8 Suot-Isla coastal essentials by shore, category, serve temperature, and price ceiling.
            </p>
        </div>
    </section>

    <section class="filter-toolbar" id="searchBox">
        <div class="container">
            <form method="GET" action="{{ route('menu.index') }}" class="filter-form">
                <div class="field-group">
                    <label for="q" class="field-label">Search Drinks or Merch</label>
                    <input type="text" id="q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Matcha, hojicha, ube, linen..." class="field-control">
                </div>

                <div class="field-group">
                    <label for="island" class="field-label">Island Series</label>
                    <select id="island" name="island" class="field-control">
                        <option value="">All Shores</option>
                        @foreach($islands as $island)
                            <option value="{{ $island->slug }}" @selected(($filters['island'] ?? '') === $island->slug)>{{ $island->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="field-group">
                    <label for="type" class="field-label">Collection</label>
                    <select id="type" name="type" class="field-control">
                        <option value="">Drinks &amp; Merch</option>
                        <option value="drinks" @selected(($filters['type'] ?? '') === 'drinks')>Craft Drinks Only</option>
                        <option value="merch" @selected(($filters['type'] ?? '') === 'merch')>Isla Merch Only</option>
                    </select>
                </div>

                <div class="field-group">
                    <label for="serve_temp" class="field-label">Serve / Temp</label>
                    <select id="serve_temp" name="serve_temp" class="field-control">
                        <option value="">Any Temperature</option>
                        <option value="hot" @selected(($filters['serve_temp'] ?? '') === 'hot')>Hot 12oz Available</option>
                        <option value="iced" @selected(($filters['serve_temp'] ?? '') === 'iced')>Iced 16oz / 20oz</option>
                    </select>
                </div>

                <div class="field-group">
                    <label for="sort" class="field-label">Sort Order</label>
                    <select id="sort" name="sort" class="field-control">
                        <option value="series" @selected(($filters['sort'] ?? '') === 'series')>By Island Series</option>
                        <option value="price_asc" @selected(($filters['sort'] ?? '') === 'price_asc')>Price: Low to High</option>
                        <option value="price_desc" @selected(($filters['sort'] ?? '') === 'price_desc')>Price: High to Low</option>
                        <option value="name_asc" @selected(($filters['sort'] ?? '') === 'name_asc')>Name: A &ndash; Z</option>
                    </select>
                </div>

                <div style="display:flex;gap:0.5rem;">
                    <button type="submit" class="tag-btn tag-btn--canopy">Filter</button>
                    <a href="{{ route('menu.index') }}" class="tag-btn tag-btn--outline">Reset</a>
                </div>
            </form>
        </div>
    </section>

    <section class="container">
        <div style="padding-top:1.25rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
            <span class="tabular" style="font-family:var(--font-mono);font-size:0.84rem;color:var(--taupe-muted);">
                Showing {{ $products->count() }} {{ \Illuminate\Support\Str::plural('item', $products->count()) }}
            </span>

            <div style="display:flex;flex-wrap:wrap;gap:0.45rem;">
                @foreach($categories as $cat)
                    <a
                        href="{{ route('menu.index', array_merge($filters, ['category' => ($filters['category'] ?? '') === $cat->slug ? null : $cat->slug])) }}"
                        class="tag-btn tag-btn--sm {{ ($filters['category'] ?? '') === $cat->slug ? 'tag-btn--canopy' : 'tag-btn--outline' }}"
                    >
                        {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>

        @if($products->isEmpty())
            <div style="margin:3rem 0;padding:3rem;border:1px solid var(--espresso-ink);background:var(--bg-limestone);text-align:center;">
                <h2 class="section-heading" style="margin-bottom:0.5rem;">No items matched those filters</h2>
                <p style="font-family:var(--font-mono);font-size:0.86rem;color:var(--taupe-muted);margin-bottom:1.25rem;">Try clearing your search keyword or switching island shore.</p>
                <a href="{{ route('menu.index') }}" class="tag-btn">View All 25 Items</a>
            </div>
        @else
            <div class="catalog-grid">
                @foreach($products as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>
        @endif
    </section>
@endsection
