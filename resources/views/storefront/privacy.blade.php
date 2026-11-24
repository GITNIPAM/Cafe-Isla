@extends('layouts.app')

@section('title', 'Privacy · Café Isla')

@section('content')
    <section class="page-intro">
        <div class="container">
            <h1 class="page-intro__title">Privacy</h1>
            @if(config('cafeisla.demo_mode'))
                <p class="page-intro__lead">Draft privacy information for the Café Isla class demo. Use sample details during the demonstration.</p>
            @endif
        </div>
    </section>
    <section class="container operate-main">
        <div class="ledger-panel form-stack support-panel">
            <h2 class="ledger-panel__title">Account and order information</h2>
            <p>We use your account details, order contents, and delivery address to manage orders and share receipts and updates. Staff see information they need for fulfillment. Your assigned rider sees the details needed to deliver your order.</p>
            <p>The site uses a session to keep you signed in and remember your bag. We count storefront visits and completed orders for the operations dashboard.</p>
            <h2 class="ledger-panel__title">Seasonal email list</h2>
            <p>When you opt in, we save your email address and the time you gave consent. Seasonal emails are pending setup. To ask about your signup or request removal, <a href="{{ route('contact') }}">contact the Café Isla team</a>.</p>
        </div>
    </section>
@endsection
