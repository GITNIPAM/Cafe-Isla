@extends('layouts.app')

@section('title', 'Track Order · Café Isla')

@section('content')
    <section class="page-intro">
        <div class="container">
            <h1 class="page-intro__title">Track Your Archipelago Order</h1>
            <p class="page-intro__lead">
                Enter any Café Isla order number (<strong>CI-2026-1003</strong>) or tracking number (<strong>TRK-1003-ISLA</strong>) to check live prep and rider updates.
            </p>
        </div>
    </section>

    <section class="container operate-main">
        <div class="ledger-panel" style="max-width:760px;">
            <form method="GET" action="{{ route('orders.track') }}" style="display:flex;gap:0.75rem;flex-wrap:wrap;">
                <input type="text" name="order" value="{{ $orderNumber }}" placeholder="Order # or Tracking # (e.g. CI-2026-1003 or TRK-1003-ISLA)" class="field-control" style="flex:1;" required>
                <button type="submit" class="tag-btn tag-btn--canopy">Track Order</button>
            </form>

            @if($orderNumber !== '' && ! $order)
                <p style="margin-top:1.25rem;font-family:var(--font-mono);color:var(--clay-coral);">
                    No order in your account matched <strong>{{ $orderNumber }}</strong>. Check your confirmation email or order history.
                </p>
            @elseif($order)
                <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--espresso-ink);">
                    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;align-items:center;">
                        <h2 style="margin:0;font-family:var(--font-mono);font-size:1.25rem;">#{{ $order->order_number }}</h2>
                        <span class="status-tag status-tag--{{ $order->status }}">{{ strtoupper($order->status) }}</span>
                    </div>

                    <p style="font-family:var(--font-mono);font-size:0.84rem;color:var(--taupe-muted);">
                        Tracking: {{ $order->delivery?->tracking_number ?? 'Unassigned' }} &middot;
                        Carrier: {{ $order->delivery?->carrier_name }} &middot;
                        Courier: {{ $order->delivery?->courier?->name ?? 'Awaiting dispatch' }}
                    </p>

                    @php
                        $delStatus = $order->delivery?->status ?? 'unassigned';
                        $idx = match ($order->status) {
                            'pending' => 1,
                            'confirmed' => 2,
                            'preparing' => 3,
                            'shipped' => $delStatus === 'in_transit' ? 5 : 4,
                            'delivered' => 6,
                            default => 0,
                        };
                    @endphp
                    <div class="timeline-bar">
                        <div class="timeline-step {{ $idx >= 1 ? 'is-reached' : '' }}">Placed</div>
                        <div class="timeline-step {{ $idx >= 2 ? 'is-reached' : '' }}">Confirmed</div>
                        <div class="timeline-step {{ $idx >= 3 ? 'is-reached' : '' }}">Preparing</div>
                        <div class="timeline-step {{ $idx >= 4 ? 'is-reached' : '' }}">Shipped</div>
                        <div class="timeline-step {{ $idx >= 5 ? 'is-reached' : '' }}">Courier Tracking</div>
                        <div class="timeline-step {{ $idx >= 6 ? 'is-reached' : '' }}">Delivered</div>
                    </div>

                    <div style="margin-top:1.25rem;">
                        <strong style="font-family:var(--font-sans);font-size:0.78rem;text-transform:uppercase;">Items Ordered</strong>
                        <ul style="margin:0.5rem 0 0 0;padding-left:1.15rem;font-size:0.92rem;">
                            @foreach($order->items as $item)
                                <li>{{ $item->quantity }}&times; {{ $item->product_name_snapshot }} ({{ $item->variant_label_snapshot }})</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endsection
