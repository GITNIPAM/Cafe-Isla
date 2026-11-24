@extends('layouts.app')

@section('title', 'Sign In & Register · Café Isla')

@section('content')
    <section class="page-intro">
        <div class="container">
            <h1 class="page-intro__title">Account &amp; Role Portal</h1>
            <p class="page-intro__lead">
                Register securely with bcrypt password verification or sign in with one of the pre-seeded demonstration roles below.
            </p>
        </div>
    </section>

    <section class="container operate-main">
        <div class="operate-grid-even">
            <div class="ledger-panel">
                <h2 class="ledger-panel__title">Sign In</h2>
                <form method="POST" action="{{ route('auth.login') }}" class="form-stack">
                    @csrf
                    <div class="field-group">
                        <label for="loginEmailInput" class="field-label">Email Address</label>
                        <input type="email" id="loginEmailInput" name="email" value="{{ old('email') }}" class="field-control" required>
                    </div>
                    <div class="field-group">
                        <label for="loginPasswordInput" class="field-label">Password</label>
                        <input type="password" id="loginPasswordInput" name="password" class="field-control" required>
                    </div>
                    <label style="display:flex;align-items:center;gap:0.5rem;font-family:var(--font-mono);font-size:0.8rem;">
                        <input type="checkbox" name="remember" value="1">
                        <span>Keep me signed in</span>
                    </label>
                    <button type="submit" class="tag-btn tag-btn--canopy">Sign In to Café Isla</button>
                </form>

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
                    <button type="button" class="demo-cred-btn" data-demo-email="barista@cafeisla.ph" data-demo-pass="Isla2026!">
                        <span><strong>Staff Barista</strong> &middot; Mateo Santos</span>
                        <span>barista@cafeisla.ph</span>
                    </button>
                    <button type="button" class="demo-cred-btn" data-demo-email="rider@cafeisla.ph" data-demo-pass="Isla2026!">
                        <span><strong>Fulfillment Courier</strong> &middot; Jomar Dela Cruz</span>
                        <span>rider@cafeisla.ph</span>
                    </button>
                </div>
            </div>

            <div class="ledger-panel">
                <h2 class="ledger-panel__title">Register Customer Account</h2>
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
                            <input type="password" id="reg_password" name="password" class="field-control" required minlength="8">
                        </div>
                        <div class="field-group">
                            <label for="reg_password_confirmation" class="field-label">Confirm Password</label>
                            <input type="password" id="reg_password_confirmation" name="password_confirmation" class="field-control" required minlength="8">
                        </div>
                    </div>
                    <button type="submit" class="tag-btn">Create Account &amp; Preserve Bag</button>
                </form>
            </div>
        </div>
    </section>
@endsection
