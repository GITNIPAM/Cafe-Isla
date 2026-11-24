@extends('layouts.app')

@section('title', 'Reset Password · Café Isla')

@push('styles')
    <meta name="referrer" content="no-referrer">
@endpush

@section('content')
    <section class="container operate-main">
        <div class="ledger-panel support-panel">
            <h1 class="ledger-panel__title">Choose a new password</h1>
            <form method="POST" action="{{ route('password.update') }}" class="form-stack">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <div class="field-group">
                    <label for="email" class="field-label">Account email</label>
                    <input type="email" id="email" name="email" value="{{ old('email', $email) }}" class="field-control" autocomplete="email" required maxlength="150">
                </div>
                <div class="field-group">
                    <label for="password" class="field-label">New password</label>
                    <input type="password" id="password" name="password" class="field-control" autocomplete="new-password" required minlength="8" maxlength="72">
                </div>
                <div class="field-group">
                    <label for="password_confirmation" class="field-label">Confirm new password</label>
                    <input type="password" id="password_confirmation" name="password_confirmation" class="field-control" autocomplete="new-password" required minlength="8" maxlength="72">
                </div>
                <button type="submit" class="tag-btn tag-btn--canopy">Change password</button>
                <a href="{{ route('password.request') }}">Request a new link</a>
            </form>
        </div>
    </section>
@endsection
