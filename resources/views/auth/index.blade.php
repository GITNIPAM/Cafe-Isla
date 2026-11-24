@extends('layouts.app')

@section('title', 'Sign In & Register · Café Isla')

@section('content')
    @php
        $showRegister = request()->query('mode') === 'register' || old('name') !== null || $errors->has('name') || $errors->has('password');
    @endphp

    <section class="page-intro">
        <div class="container">
            <h1 class="page-intro__title">Account &amp; Role Portal</h1>
            <p class="page-intro__lead">
                Sign in to your Café Isla account or create a new customer account to place and track orders.
            </p>
        </div>
    </section>

    <section class="container operate-main">
        <div style="max-width:620px;margin-inline:auto;">
            <div class="ledger-panel" id="signInCard" @if($showRegister) hidden @endif>
                <h2 class="ledger-panel__title">Sign In</h2>
                <form method="POST" action="{{ route('auth.login') }}" class="form-stack">
                    @csrf
                    <div class="field-group">
                        <label for="loginEmailInput" class="field-label">Email Address</label>
                        <input type="email" id="loginEmailInput" name="email" value="{{ old('email') }}" class="field-control" required>
                    </div>
                    <div class="field-group">
                        <label for="loginPasswordInput" class="field-label">Password</label>
                        @include('partials.password-input', ['id' => 'loginPasswordInput', 'name' => 'password'])
                    </div>
                    <label style="display:flex;align-items:center;gap:0.5rem;font-family:var(--font-mono);font-size:0.8rem;">
                        <input type="checkbox" name="remember" value="1">
                        <span>Keep me signed in</span>
                    </label>
                    <button type="submit" class="tag-btn tag-btn--canopy">Sign In to Café Isla</button>
                </form>

                <div style="margin-top:1.15rem;padding-top:1rem;border-top:1px solid var(--hairline);display:flex;justify-content:space-between;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                    <span style="font-family:var(--font-mono);font-size:0.82rem;color:var(--taupe-muted);">New to Café Isla?</span>
                    <button type="button" id="showRegisterBtn" class="tag-btn tag-btn--outline tag-btn--sm">Create New Account</button>
                </div>

                @if(config('cafeisla.demo_mode'))
                <div class="demo-cred-box">
                    <div class="field-label">One-Click Role Autofill (Password: Isla2026!)</div>
                    <button type="button" class="demo-cred-btn" data-demo-email="customer@cafeisla.ph" data-demo-pass="Isla2026!">
                        <span><strong>Customer</strong> &middot; Aria Mendoza</span>
                        <span>customer@cafeisla.ph</span>
                    </button>
                    <button type="button" class="demo-cred-btn" data-demo-email="admin@cafeisla.ph" data-demo-pass="Isla2026!">
                        <span><strong>Admin</strong> &middot; Camille Reyes</span>
                        <span>admin@cafeisla.ph</span>
                    </button>
                    <button type="button" class="demo-cred-btn" data-demo-email="rider@cafeisla.ph" data-demo-pass="Isla2026!">
                        <span><strong>Fulfillment Courier</strong> &middot; Jomar Dela Cruz</span>
                        <span>rider@cafeisla.ph</span>
                    </button>
                </div>
                @endif
            </div>

            <div class="ledger-panel" id="registerCard" @if(! $showRegister) hidden @endif>
                <h2 class="ledger-panel__title">Create New Account</h2>
                <form method="POST" action="{{ route('auth.register') }}" class="form-stack">
                    @csrf
                    <div class="field-group">
                        <label for="reg_name" class="field-label">Full Name</label>
                        <input type="text" id="reg_name" name="name" value="{{ old('name') }}" class="field-control" required>
                    </div>
                    <div class="form-row-2">
                        <div class="field-group">
                            <label for="reg_email" class="field-label">Email Address</label>
                            <input type="email" id="reg_email" name="email" value="{{ old('email') }}" class="field-control" required>
                        </div>
                        <div class="field-group">
                            <label for="reg_phone" class="field-label">Mobile Number</label>
                            <input type="text" id="reg_phone" name="phone" value="{{ old('phone') }}" placeholder="0917-..." class="field-control">
                        </div>
                    </div>
                    <div class="form-row-2">
                        <div class="field-group">
                            <label for="reg_password" class="field-label">Password (min 8 chars)</label>
                            @include('partials.password-input', ['id' => 'reg_password', 'name' => 'password', 'minlength' => 8])
                        </div>
                        <div class="field-group">
                            <label for="reg_password_confirmation" class="field-label">Confirm Password</label>
                            @include('partials.password-input', ['id' => 'reg_password_confirmation', 'name' => 'password_confirmation', 'minlength' => 8])
                        </div>
                    </div>
                    <button type="submit" class="tag-btn">Create Account &amp; Preserve Bag</button>
                </form>

                <div style="margin-top:1.15rem;padding-top:1rem;border-top:1px solid var(--hairline);display:flex;justify-content:space-between;align-items:center;gap:0.75rem;flex-wrap:wrap;">
                    <span style="font-family:var(--font-mono);font-size:0.82rem;color:var(--taupe-muted);">Already have an account?</span>
                    <button type="button" id="showSignInBtn" class="tag-btn tag-btn--outline tag-btn--sm">Back to Sign In</button>
                </div>
            </div>
        </div>
    </section>

    <script>
        (function () {
            var signInCard = document.getElementById('signInCard');
            var registerCard = document.getElementById('registerCard');
            var showRegisterBtn = document.getElementById('showRegisterBtn');
            var showSignInBtn = document.getElementById('showSignInBtn');

            if (showRegisterBtn && signInCard && registerCard) {
                showRegisterBtn.addEventListener('click', function () {
                    signInCard.hidden = true;
                    registerCard.hidden = false;
                });
            }
            if (showSignInBtn && signInCard && registerCard) {
                showSignInBtn.addEventListener('click', function () {
                    registerCard.hidden = true;
                    signInCard.hidden = false;
                });
            }
        })();
    </script>
@endsection
