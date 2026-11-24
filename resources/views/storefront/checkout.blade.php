@extends('layouts.app')

@section('title', 'Structured Checkout · Café Isla')

@section('content')
    <section class="page-intro">
        <div class="container">
            <h1 class="page-intro__title">Checkout &amp; Payment</h1>
            <p class="page-intro__lead">
                Check delivery availability and review your total. This class demo simulates payments.
            </p>
        </div>
    </section>

    <section class="container operate-main">
        <form method="POST" action="{{ route('checkout.store') }}" class="operate-grid-2" data-checkout-quote="{{ route('checkout.quote') }}" data-checkout-coupon="{{ $summary['coupon_code'] }}">
            @csrf
            <input type="hidden" name="quoted_total" value="{{ $deliveryError ? '' : $summary['grand_total'] }}" data-quoted-total>

            <div class="form-stack">
                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">1. Billing Details</h2>
                    <div class="form-stack">
                        <div class="form-row-2">
                            <div class="field-group">
                                <label for="billing_name" class="field-label">Full Name</label>
                                <input type="text" id="billing_name" name="billing_name" value="{{ old('billing_name', $user->name) }}" class="field-control" required>
                            </div>
                            <div class="field-group">
                                <label for="billing_phone" class="field-label">Mobile Number</label>
                                <input type="tel" id="billing_phone" name="billing_phone" value="{{ old('billing_phone', $user->phone) }}" class="field-control" required>
                            </div>
                        </div>
                        <div class="field-group">
                            <label for="billing_email" class="field-label">Email for Confirmation &amp; Shipping Updates</label>
                            <input type="email" id="billing_email" name="billing_email" value="{{ old('billing_email', $user->email) }}" class="field-control" required>
                        </div>
                    </div>
                </div>

                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">2. Shipping Address &amp; Delivery Zone</h2>
                    <div class="form-stack">
                        @if($addresses->isNotEmpty())
                            <div class="field-group">
                                <label for="saved_address_id" class="field-label">Choose Saved Address (or leave empty to enter new below)</label>
                                <select id="saved_address_id" name="saved_address_id" class="field-control">
                                    <option value="">-- Enter New Shipping Address Below --</option>
                                    @foreach($addresses as $addr)
                                        <option value="{{ $addr->id }}" data-delivery-zone="{{ $addr->delivery_zone }}" @selected($selectedAddressId == $addr->id)>
                                            {{ $addr->label }} — {{ $addr->formattedAddress() }} ({{ $addr->delivery_zone === 'local_express' ? 'Local Express' : 'Provincial Parcel' }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <fieldset class="form-stack checkout-address-fields" data-new-address>
                        <div class="form-row-2">
                            <div class="field-group">
                                <label for="shipping_recipient" class="field-label">Recipient Name</label>
                                <input type="text" id="shipping_recipient" name="shipping_recipient" value="{{ old('shipping_recipient', $user->name) }}" class="field-control">
                            </div>
                            <div class="field-group">
                                <label for="shipping_phone" class="field-label">Recipient Phone</label>
                                <input type="tel" id="shipping_phone" name="shipping_phone" value="{{ old('shipping_phone', $user->phone) }}" class="field-control">
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="shipping_address_line" class="field-label">Street / Unit / Barangay</label>
                            <input type="text" id="shipping_address_line" name="shipping_address_line" value="{{ old('shipping_address_line') }}" placeholder="e.g. 18 Urbiztondo Coastal Lane" class="field-control">
                        </div>

                        <div class="form-row-2">
                            <div class="field-group">
                                <label for="shipping_city" class="field-label">City / Municipality</label>
                                <input type="text" id="shipping_city" name="shipping_city" value="{{ old('shipping_city') }}" class="field-control">
                            </div>
                            <div class="field-group">
                                <label for="shipping_province" class="field-label">Province</label>
                                <input type="text" id="shipping_province" name="shipping_province" value="{{ old('shipping_province') }}" class="field-control">
                            </div>
                        </div>

                        <div class="field-group">
                            <label for="shipping_postal_code" class="field-label">Postal Code</label>
                            <input type="text" id="shipping_postal_code" name="shipping_postal_code" value="{{ old('shipping_postal_code') }}" class="field-control">
                        </div>

                        <label style="display:flex;align-items:center;gap:0.5rem;font-family:var(--font-mono);font-size:0.8rem;">
                            <input type="checkbox" name="save_address" value="1" @checked(old('save_address'))>
                            <span>Save new address to my customer profile</span>
                        </label>
                        </fieldset>
                        <div class="field-group">
                            <label for="delivery_zone" class="field-label">Delivery Method</label>
                            <select id="delivery_zone" name="delivery_zone" class="field-control" required>
                                <option value="local_express" @selected($selectedZone === 'local_express')>Local Express (₱65 · Required for Drinks)</option>
                                <option value="provincial_parcel" @selected($selectedZone === 'provincial_parcel' && ! $summary['has_perishables']) @disabled($summary['has_perishables'])>Nationwide Parcel (₱120 · Merch Only)</option>
                            </select>
                            <a href="{{ route('delivery.policy') }}">See demo delivery coverage</a>
                        </div>
                    </div>
                </div>

                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">3. Mock Payment Method</h2>
                    <div class="form-stack">
                        <div class="variant-selector-grid" style="margin-block:0;">
                            <label class="variant-chip">
                                <input type="radio" name="payment_method" value="gcash" @checked(old('payment_method', 'gcash') === 'gcash')>
                                <span>GCash Instant</span>
                            </label>
                            <label class="variant-chip">
                                <input type="radio" name="payment_method" value="cod" @checked(old('payment_method') === 'cod')>
                                <span>Cash on Delivery (COD)</span>
                            </label>
                        </div>

                        <div class="form-row-2">
                            <div class="field-group" id="gcashRefGroup" style="{{ old('payment_method', 'gcash') === 'cod' ? 'display:none;' : '' }}">
                                <label for="payment_account_ref" class="field-label">Demo Payment Reference (Optional)</label>
                                <input type="text" id="payment_account_ref" name="payment_account_ref" value="{{ old('payment_account_ref') }}" placeholder="e.g. DEMO-8841" class="field-control">
                            </div>
                            <div class="field-group">
                                <label for="order_notes" class="field-label">Barista / Rider Note</label>
                                <input type="text" id="order_notes" name="order_notes" value="{{ old('order_notes') }}" placeholder="e.g. Call upon arrival" class="field-control">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <aside class="ledger-panel ledger-panel--canopy">
                <h2 class="ledger-panel__title">Checkout Summary</h2>
                <div class="form-stack">
                    @foreach($summary['items'] as $item)
                        <div style="display:flex;justify-content:space-between;gap:0.75rem;padding-bottom:0.6rem;border-bottom:1px solid rgba(246,241,230,0.2);font-size:0.86rem;">
                            <div>
                                <strong style="font-family:var(--font-sans);font-size:0.78rem;text-transform:uppercase;">{{ $item['product_name'] }}</strong>
                                <div style="font-family:var(--font-mono);font-size:0.74rem;opacity:0.78;">
                                    {{ $item['variant_label'] }} &times; <span data-quote-quantity="{{ $item['id'] }}">{{ $item['quantity'] }}</span>
                                    @if($item['custom_notes'])<br>{{ $item['custom_notes'] }}@endif
                                </div>
                            </div>
                            <span class="tabular" data-quote-item="{{ $item['id'] }}" style="font-family:var(--font-mono);">₱{{ number_format($item['line_total'], 2) }}</span>
                        </div>
                    @endforeach

                    <div style="font-family:var(--font-mono);font-size:0.86rem;display:flex;flex-direction:column;gap:0.4rem;padding-top:0.4rem;">
                        <div style="display:flex;justify-content:space-between;">
                            <span>Subtotal</span>
                            <span class="tabular" data-quote-subtotal>₱{{ number_format($summary['subtotal'], 2) }}</span>
                        </div>
                        @if($summary['coupon_code'])
                            <div @if($summary['discount_amount'] <= 0) hidden @endif data-quote-discount-row>
                                <span>Coupon ({{ $summary['coupon_code'] }})</span>
                                <span class="tabular" data-quote-discount>-₱{{ number_format($summary['discount_amount'], 2) }}</span>
                            </div>
                        @endif
                        <div style="display:flex;justify-content:space-between;">
                            <span>Delivery Fee</span>
                            <span class="tabular" data-quote-fee>{{ $deliveryError ? '—' : ($summary['delivery_fee'] == 0 ? 'FREE' : '₱'.number_format($summary['delivery_fee'], 2)) }}</span>
                        </div>
                        <div style="display:flex;justify-content:space-between;font-size:1.15rem;font-weight:700;padding-top:0.6rem;border-top:1px solid rgba(246,241,230,0.35);">
                            <span>Amount Due</span>
                            <span class="tabular" data-quote-total>{{ $deliveryError ? '—' : '₱'.number_format($summary['grand_total'], 2) }}</span>
                        </div>
                    </div>

                    <p data-quote-status role="status">{{ $deliveryError }}</p>
                    <noscript>After changing delivery details, submit the form to refresh the total, then review it before placing your order.</noscript>
                    <button type="submit" class="tag-btn" data-place-order style="width:100%;margin-top:0.5rem;">
                        Place Order &amp; Dispatch Receipt
                    </button>
                    <a href="{{ route('cart.index') }}" class="tag-btn tag-btn--outline" style="width:100%;border-color:rgba(246,241,230,0.5);color:#F6F1E6;">
                        Back to Bag
                    </a>
                </div>
            </aside>
        </form>
    </section>
@endsection

@push('scripts')
    <script src="{{ asset('assets/js/checkout.js') }}" defer></script>
@endpush
