@extends('layouts.app')

@section('title', 'Café Isla · Slow sips from the archipelago')

@section('content')
    <!-- Framed Sunlit Editorial Hero -->
    <section class="editorial-hero">
        <div class="container">
            <div class="hero-frame">
                <img src="{{ asset('assets/images/editorial/hero-island-cafe.jpg') }}" alt="Sunlit Philippine coastal café limestone bar with chawan bowl, bamboo whisk, and iced ceremonial matcha latte">
            </div>

            <div class="hero-thesis">
                <h1 class="hero-thesis__h1">Slow sips brewed from four Philippine shores.</h1>

                <div class="hero-thesis__copy">
                    <p class="hero-thesis__lead">
                        Café Isla translates our archipelago's surf towns and lagoons into small-batch ceremonial matcha, roasted hojicha, reef espresso, and sun-washed coastal apparel carried forward from Suot-Isla.
                    </p>
                    <div class="hero-thesis__actions">
                        <a href="{{ route('menu.index') }}" class="tag-btn">Explore Full Menu</a>
                        <a href="{{ route('menu.index', ['island' => 'la-union']) }}" class="tag-btn tag-btn--canopy">La Union Matcha Bar</a>
                        @if(config('cafeisla.demo_mode'))
                            <a href="{{ route('diagrams.index') }}" class="tag-btn tag-btn--outline">ERD &amp; Use Cases</a>
                        @endif
                    </div>
                    <span class="hero-thesis__meta">
                        Four Island Chapters &middot; Fresh Rider Express (Drinks) &amp; Nationwide Parcel (Isla Merch)
                    </span>
                </div>
            </div>
        </div>
    </section>

    <!-- Interactive 4-Island Bar Switcher -->
    <section class="island-bar-section" id="island-series">
        <div class="container">
            <div class="island-bar-header">
                <h2 class="section-heading">Pour by Island Series</h2>
                <div class="island-tabs" role="tablist" aria-label="Philippine Island Drink Chapters">
                    @foreach($islands as $idx => $island)
                        <button
                            type="button"
                            class="island-tab {{ $idx === 0 ? 'is-active' : '' }}"
                            role="tab"
                            aria-selected="{{ $idx === 0 ? 'true' : 'false' }}"
                            data-island-target="{{ $island->slug }}"
                        >
                            <span class="island-tab__dot" style="background-color: {{ $island->accent_hex }};"></span>
                            <span>{{ str_replace(' Series', '', $island->name) }}</span>
                        </button>
                    @endforeach
                </div>
            </div>

            @foreach($islands as $idx => $island)
                @php
                    $drinks = $island->products->where('is_perishable', true);
                @endphp
                <div class="island-panel {{ $idx === 0 ? 'is-active' : '' }}" data-island-panel="{{ $island->slug }}">
                    <div class="island-panel__media">
                        <img src="{{ asset($island->hero_image) }}" alt="{{ $island->name }} coastal drink showcase">
                        <div class="island-panel__coord-strip">
                            <span>{{ $island->subtitle }}</span>
                            <span>{{ $island->coordinates }}</span>
                        </div>
                    </div>

                    <div class="island-panel__details">
                        <h3 class="island-panel__title">{{ $island->name }}</h3>
                        <p class="island-panel__story">{{ $island->terroir_story }}</p>

                        <div class="quick-pour-list">
                            @foreach($drinks as $drink)
                                <div class="quick-pour-row">
                                    <div>
                                        <a href="{{ route('product.show', $drink->slug) }}" class="quick-pour-row__name">
                                            {{ $drink->name }}
                                        </a>
                                        <p class="quick-pour-row__desc">{{ $drink->short_excerpt }}</p>
                                    </div>
                                    <div class="quick-pour-row__controls">
                                        @foreach($drink->variants as $variant)
                                            <button
                                                type="button"
                                                class="variant-pour-btn tabular"
                                                data-quick-pour="{{ $variant->id }}"
                                                @disabled($variant->stock_qty < 1)
                                            >
                                                <span>{{ $variant->label }}</span>
                                                <strong>₱{{ number_format((float) $variant->price, 0) }}</strong>
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>

                        <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:0.75rem;">
                            <a href="{{ route('menu.index', ['island' => $island->slug]) }}" class="tag-btn tag-btn--outline tag-btn--sm">
                                View {{ str_replace(' Series', '', $island->name) }} Drinks &amp; Goods
                            </a>
                            <span style="font-family:var(--font-mono);font-size:0.78rem;color:var(--taupe-muted);">
                                Click any size pill above to pour directly into your bag
                            </span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    <!-- 1/3 Photo + 2/3 Editorial Menu Ledger (Flora Wellness Cafe Pacing) -->
    <section class="menu-split-section">
        <div class="container">
            <div class="menu-split-grid">
                <aside class="menu-split-aside">
                    <div class="menu-split-photo">
                        <img src="{{ asset('assets/images/editorial/menu-table-ritual.jpg') }}" alt="Monaliza Caramel Matcha, Halaya Dirty Ube Latte, and Big Lagoon Butterfly Pea Limonada on sunlit travertine table">
                    </div>
                    <p class="menu-split-caption">
                        Whisked ceremonial Uji matcha, Batangas barako, purple halaya, and fresh Palawan botanicals—crafted per cup for local rider express.
                    </p>
                    <div>
                        <a href="{{ route('menu.index') }}" class="tag-btn tag-btn--canopy">Filter &amp; Search Full Catalog</a>
                    </div>
                </aside>

                <div>
                    <div class="section-top-row">
                        <div>
                            <h2 class="section-heading">Explore Our Island Ledger</h2>
                            <p class="section-subcopy">17 signature beverages arranged by coastal shore. Select any drink for tasting notes and barista notes.</p>
                        </div>
                    </div>

                    <div class="menu-ledger">
                        @foreach($islands as $island)
                            @php
                                $drinks = $island->products->where('is_perishable', true);
                            @endphp
                            <div class="menu-chapter">
                                <div class="menu-chapter__header">
                                    <h3 class="menu-chapter__title">{{ $island->name }}</h3>
                                    <span class="menu-chapter__coord">{{ $island->coordinates }}</span>
                                </div>

                                @foreach($drinks as $drink)
                                    @php
                                        $minPrice = $drink->variants->min('price') ?? $drink->base_price;
                                        $maxPrice = $drink->variants->max('price') ?? $drink->base_price;
                                        $priceDisplay = $minPrice == $maxPrice
                                            ? '₱'.number_format((float) $minPrice, 0)
                                            : '₱'.number_format((float) $minPrice, 0).' / ₱'.number_format((float) $maxPrice, 0);
                                    @endphp
                                    <div class="menu-line-item">
                                        <div class="menu-line-item__top">
                                            <a href="{{ route('product.show', $drink->slug) }}" class="menu-line-item__name">{{ $drink->name }}</a>
                                            <span class="menu-line-item__price tabular">{{ $priceDisplay }}</span>
                                        </div>
                                        <p class="menu-line-item__notes">{{ $drink->short_excerpt }}</p>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </section>

    <div class="wave-divider" aria-hidden="true">
        <svg viewBox="0 0 600 18" fill="none" stroke="#C4553A" stroke-width="1.5">
            <path d="M2 9C25 2 50 16 75 9C100 2 125 16 150 9C175 2 200 16 225 9C250 2 275 16 300 9C325 2 350 16 375 9C400 2 425 16 450 9C475 2 500 16 525 9C550 2 575 16 598 9"/>
        </svg>
    </div>

    <!-- Isla Merch Capsule Carousel (Adapted from Suot-Isla) -->
    <section class="merch-section">
        <div class="container">
            <div class="section-top-row">
                <div>
                    <h2 class="section-heading">Isla Merch Capsule</h2>
                    <p class="section-subcopy">Sun-washed tees, corduroy caps, linen polos, and woven pandan totes carried over from Suot-Isla—eligible for nationwide parcel delivery and return restocking.</p>
                </div>
                <a href="{{ route('menu.index', ['type' => 'merch']) }}" class="tag-btn">Shop All Merch</a>
            </div>

            <div class="merch-track" id="merchCarouselTrack">
                @foreach($merchProducts as $product)
                    @include('partials.product-card', ['product' => $product])
                @endforeach
            </div>

            <div class="merch-progress-rail" aria-hidden="true">
                <div class="merch-progress-fill" id="merchCarouselProgress"></div>
            </div>
        </div>
    </section>
@endsection
