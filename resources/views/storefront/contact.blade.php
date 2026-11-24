@extends('layouts.app')

@section('title', 'Contact · Café Isla')

@section('content')
    <section class="page-intro">
        <div class="container">
            <h1 class="page-intro__title">Contact Café Isla</h1>
            <p class="page-intro__lead">Questions about your order, the menu, or email preferences?</p>
        </div>
    </section>
    <section class="container operate-main">
        <div class="ledger-panel form-stack support-panel">
            <h2 class="ledger-panel__title">Email the team</h2>
            <p><a href="mailto:care.cafeisla@gmail.com">care.cafeisla@gmail.com</a></p>
            @if(config('cafeisla.demo_mode'))
                <p>This address appears in the class project. Live customer support and café visiting hours are pending confirmation.</p>
            @endif
            <p>For order updates or a return, open your <a href="{{ route('account.index') }}">order history</a>. Check <a href="{{ route('delivery.policy') }}">delivery and returns</a> for coverage and next steps.</p>
        </div>
    </section>
@endsection
