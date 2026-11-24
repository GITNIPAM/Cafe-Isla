@extends('layouts.app')

@section('title', 'Delivery & Returns · Café Isla')

@section('content')
    <section class="page-intro">
        <div class="container">
            <h1 class="page-intro__title">Delivery &amp; Returns</h1>
            @if(config('cafeisla.demo_mode'))
                <p class="page-intro__lead">Draft policy for the Café Isla class demo. Checkout simulates payments and refunds.</p>
            @endif
        </div>
    </section>
    <section class="container operate-main">
        <div class="ledger-panel form-stack support-panel">
            <h2 class="ledger-panel__title">Delivery coverage and rates</h2>
            <p>Fresh drinks go by Local Express. Orders with only merch can use Local Express where available, or nationwide parcel.</p>
            <p>Local Express coverage{{ config('cafeisla.demo_mode') ? ' for this demo' : '' }} is {{ implode(' and ', config('cafeisla.local_delivery_areas')) }}.</p>
            <p>Local Express costs ₱{{ number_format(\App\Services\CartService::LOCAL_EXPRESS_FEE) }} and nationwide parcel costs ₱{{ number_format(\App\Services\CartService::PROVINCIAL_PARCEL_FEE) }}. Delivery is free at ₱{{ number_format(\App\Services\CartService::FREE_DELIVERY_THRESHOLD) }} or more after discounts. Checkout shows your fee and total before you place the order.</p>
            <h2 class="ledger-panel__title">Cancellations and returns</h2>
            <p>You can cancel while an order is pending. For a shipped or delivered order, request a return or report a drink spill from <a href="{{ route('account.index') }}">your account</a>. Include the affected item and what happened so the team can review the request.</p>
        </div>
    </section>
@endsection
