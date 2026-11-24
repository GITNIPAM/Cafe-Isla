@extends('layouts.app')

@section('title', 'Forgot Password · Café Isla')

@section('content')
    <section class="container operate-main">
        <div class="ledger-panel support-panel">
            <h1 class="ledger-panel__title">Forgot your password?</h1>
            <p>Enter your account email to request a reset link.</p>
            <form method="POST" action="{{ route('password.email') }}" class="form-stack">
                @csrf
                <div class="field-group">
                    <label for="email" class="field-label">Account email</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" class="field-control" autocomplete="email" required maxlength="150">
                </div>
                <button type="submit" class="tag-btn tag-btn--canopy">Send reset link</button>
                <a href="{{ route('auth.index') }}">Back to sign in</a>
            </form>
        </div>
    </section>
@endsection
