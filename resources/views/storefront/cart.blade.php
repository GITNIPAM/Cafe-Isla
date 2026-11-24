@extends('layouts.app')

@section('title', 'Shopping Bag · Café Isla')

@section('content')
    <section class="page-intro">
        <div class="container">
            <h1 class="page-intro__title">Your Isla Shopping Bag</h1>
            <p class="page-intro__lead">
                Persistent across every page. Apply promo code <strong>ISLA10</strong> (10% off) or <strong>FREESIP</strong> (free local delivery over ₱500).
            </p>
        </div>
    </section>

    <section class="container operate-main">
        @if(empty($summary['items']))
            <div class="ledger-panel" style="text-align:center;padding:3rem;">
                <h2 class="section-heading" style="margin-bottom:0.6rem;">Nothing poured yet</h2>
                <p style="font-family:var(--font-mono);font-size:0.86rem;color:var(--taupe-muted);margin-bottom:1.5rem;">
                    Explore our four Island Drink Series or Suot-Isla coastal apparel to start your bag.
                </p>
                <a href="{{ route('menu.index') }}" class="tag-btn tag-btn--canopy">Browse Menu &amp; Merch</a>
            </div>
        @else
            <div class="operate-grid-2">
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Item &amp; Variant</th>
                                <th>Unit Price</th>
                                <th>Quantity</th>
                                <th>Line Total</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($summary['items'] as $item)
                                <tr>
                                    <td>
                                        <div style="display:flex;gap:0.85rem;align-items:center;">
                                            <img src="{{ $item['image_url'] }}" alt="{{ $item['product_name'] }}" style="width:54px;height:66px;object-fit:cover;border:1px solid var(--espresso-ink);">
                                            <div>
                                                <a href="{{ route('product.show', $item['product_slug']) }}" style="font-family:var(--font-sans);font-size:0.82rem;font-weight:700;text-transform:uppercase;text-decoration:none;">
                                                    {{ $item['product_name'] }}
                                                </a>
                                                <div style="font-family:var(--font-mono);font-size:0.76rem;color:var(--taupe-muted);">
                                                    {{ $item['island_name'] }} &middot; {{ $item['variant_label'] }}
                                                </div>
                                                @if($item['is_perishable'] || $item['custom_notes'] !== null)
                                                    <label class="field-label" for="bag-note-{{ $item['id'] }}">{{ $item['is_perishable'] ? 'Drink instructions' : 'Item instructions' }}</label>
                                                    <input type="text" name="custom_notes" id="bag-note-{{ $item['id'] }}" form="bag-item-{{ $item['id'] }}" value="{{ $item['custom_notes'] }}" maxlength="160" class="field-control">
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="tabular" style="font-family:var(--font-mono);">₱{{ number_format($item['unit_price'], 2) }}</td>
                                    <td>
                                        <form id="bag-item-{{ $item['id'] }}" method="POST" action="{{ route('cart.items.update', $item['id']) }}" style="display:flex;gap:0.35rem;align-items:center;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="number" name="quantity" aria-label="Quantity for {{ $item['product_name'] }}" value="{{ $item['quantity'] }}" min="0" max="{{ min(max(25, $item['quantity']), $item['max_stock']) }}" class="field-control tabular" style="width:70px;padding:0.35rem 0.5rem;">
                                            <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Update</button>
                                        </form>
                                    </td>
                                    <td class="tabular" style="font-family:var(--font-mono);font-weight:700;">₱{{ number_format($item['line_total'], 2) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('cart.items.remove', $item['id']) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Remove</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <aside class="ledger-panel">
                    <h2 class="ledger-panel__title">Order Summary</h2>

                    <div class="form-stack">
                        @if($summary['coupon_code'])
                            <div style="display:flex;justify-content:space-between;align-items:center;padding:0.6rem 0.75rem;border:1px solid var(--canopy-deep);background:#E3EDE4;font-family:var(--font-mono);font-size:0.82rem;">
                                <span>Applied Code: <strong>{{ $summary['coupon_code'] }}</strong></span>
                                <form method="POST" action="{{ route('cart.coupon.remove') }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" style="background:none;border:0;text-decoration:underline;cursor:pointer;">Remove</button>
                                </form>
                            </div>
                        @else
                            <form method="POST" action="{{ route('cart.coupon.apply') }}" style="display:flex;gap:0.5rem;">
                                @csrf
                                <input type="text" name="code" placeholder="Enter ISLA10 or FREESIP" class="field-control" required>
                                <button type="submit" class="tag-btn tag-btn--outline">Apply</button>
                            </form>
                        @endif

                        <div style="border-top:1px solid var(--hairline);padding-top:0.85rem;font-family:var(--font-mono);font-size:0.88rem;display:flex;flex-direction:column;gap:0.45rem;">
                            <div style="display:flex;justify-content:space-between;">
                                <span>Subtotal</span>
                                <span class="tabular">₱{{ number_format($summary['subtotal'], 2) }}</span>
                            </div>
                            @if($summary['discount_amount'] > 0)
                                <div style="display:flex;justify-content:space-between;color:var(--clay-coral);">
                                    <span>Discount ({{ $summary['coupon_code'] }})</span>
                                    <span class="tabular">-₱{{ number_format($summary['discount_amount'], 2) }}</span>
                                </div>
                            @endif
                            <div style="display:flex;justify-content:space-between;">
                                <span>Delivery ({{ $summary['has_perishables'] ? 'Local Rider Express' : 'Standard' }})</span>
                                <span class="tabular">{{ $summary['delivery_fee'] == 0 ? 'FREE' : '₱'.number_format($summary['delivery_fee'], 2) }}</span>
                            </div>
                            <div style="display:flex;justify-content:space-between;padding-top:0.65rem;border-top:1px solid var(--espresso-ink);font-size:1.08rem;font-weight:700;">
                                <span>Grand Total</span>
                                <span class="tabular">₱{{ number_format($summary['grand_total'], 2) }}</span>
                            </div>
                        </div>

                        <p style="margin:0;font-family:var(--font-mono);font-size:0.76rem;color:var(--taupe-muted);">
                            @if($summary['has_perishables'])
                                Fresh beverages are dispatched via Local Express Rider (₱65 flat fee, free at ₱1,200).
                            @else
                                Merch-only bags qualify for Local Express (₱65) or Nationwide Parcel (₱120), free over ₱1,200.
                            @endif
                        </p>

                        <a href="{{ route('checkout.index') }}" class="tag-btn tag-btn--canopy" style="width:100%;">
                            Proceed to Structured Checkout
                        </a>
                        <a href="{{ route('menu.index') }}" class="tag-btn tag-btn--outline" style="width:100%;">
                            Continue Browsing
                        </a>
                    </div>
                </aside>
            </div>
        @endif
    </section>
@endsection
