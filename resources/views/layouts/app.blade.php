<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Café Isla · Slow sips from the archipelago')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Courier+Prime:wght@400;700&family=Fraunces:ital,opsz,wght@0,9..144,400;0,9..144,600;1,9..144,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/isla.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/operate.css') }}">
    @stack('styles')
</head>
<body id="top">
    @if(! View::hasSection('operator_shell'))
    <div class="announcement-strip" id="announcementStrip">
        <span class="announcement-strip__text">Code ISLA10 &middot; 10% off archipelago drinks &amp; merch &middot; Free local rider delivery over ₱1,200</span>
        <button type="button" class="announcement-strip__close" id="closeAnnouncement" aria-label="Dismiss announcement">
            <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.6" aria-hidden="true">
                <path d="M3 3L13 13M13 3L3 13"/>
            </svg>
        </button>
    </div>

    <header class="site-header">
        <div class="site-header__inner">
            <a href="{{ route('home') }}" class="brand-lockup" aria-label="Café Isla Home">
                @include('partials.logo-seal', ['size' => 54, 'class' => 'brand-lockup__seal'])
                <span class="brand-lockup__text">
                    <span class="brand-lockup__script">Café Isla<span class="brand-lockup__dot" aria-hidden="true">.</span></span>
                </span>
            </a>

            <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-expanded="false" aria-controls="primaryNav">
                Menu
            </button>

            <nav class="site-nav" id="primaryNav" aria-label="Primary Navigation">
                <a href="{{ route('home') }}" class="site-nav__link {{ request()->routeIs('home') ? 'is-active' : '' }}">Home</a>
                <a href="{{ route('menu.index') }}" class="site-nav__link {{ request()->routeIs('menu.index') && request('type') !== 'merch' ? 'is-active' : '' }}">Menu</a>
                <a href="{{ route('menu.index', ['type' => 'merch']) }}" class="site-nav__link {{ request('type') === 'merch' ? 'is-active' : '' }}">Isla Merch</a>
                <a href="{{ route('orders.track') }}" class="site-nav__link {{ request()->routeIs('orders.track') ? 'is-active' : '' }}">Track</a>
            </nav>

            <div class="site-actions">
                <a href="{{ route('menu.index') }}#searchBox" class="header-action-btn" title="Search Menu & Merch">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="7" cy="7" r="5"/>
                        <path d="M11 11L14.5 14.5"/>
                    </svg>
                    <span>Search</span>
                </a>

                @auth
                <button type="button" class="header-action-btn" data-open-inbox aria-haspopup="dialog" aria-controls="inboxDrawer">
                    <svg width="15" height="14" viewBox="0 0 16 14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <rect x="1" y="1.5" width="14" height="11"/>
                        <path d="M1 2.5L8 8L15 2.5"/>
                    </svg>
                    <span>Inbox</span>
                    <span class="header-action-count tabular">{{ $sharedEmailCount ?? 0 }}</span>
                </button>
                @endauth

                @auth
                    @if(auth()->user()->isOperator())
                        <a href="{{ route('admin.index') }}" class="header-action-btn">
                            {{ auth()->user()->role === 'courier' ? 'Rider Dispatch' : 'Portal (Admin)' }}
                        </a>
                    @else
                        <a href="{{ route('account.index') }}" class="header-action-btn">Account</a>
                    @endif
                @else
                    <a href="{{ route('auth.index') }}" class="header-action-btn">Sign In</a>
                @endauth

                <button type="button" class="header-action-btn" data-open-bag aria-haspopup="dialog" aria-controls="bagDrawer">
                    <svg width="14" height="15" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <path d="M2 5H14L13 14.5H3L2 5Z"/>
                        <path d="M5.5 5V3.5C5.5 2.1 6.6 1 8 1C9.4 1 10.5 2.1 10.5 3.5V5"/>
                    </svg>
                    <span>Bag</span>
                    <span class="header-action-count tabular" data-bag-count>{{ $sharedCartSummary['count'] ?? 0 }}</span>
                </button>
            </div>
        </div>
    </header>
    @endif

    @if(session('success'))
        <div class="flash-banner flash-banner--success" role="status">
            <span>{{ session('success') }}</span>
            <button type="button" onclick="this.parentElement.remove()" style="background:none;border:0;cursor:pointer;font-family:var(--font-sans);font-size:0.72rem;text-transform:uppercase;">Dismiss</button>
        </div>
    @endif

    @if(session('error'))
        <div class="flash-banner flash-banner--error" role="alert">
            <span>{{ session('error') }}</span>
            <button type="button" onclick="this.parentElement.remove()" style="background:none;border:0;cursor:pointer;font-family:var(--font-sans);font-size:0.72rem;text-transform:uppercase;">Dismiss</button>
        </div>
    @endif

    @if(session('info'))
        <div class="flash-banner flash-banner--info" role="status">
            <span>{{ session('info') }}</span>
            <button type="button" onclick="this.parentElement.remove()" style="background:none;border:0;cursor:pointer;font-family:var(--font-sans);font-size:0.72rem;text-transform:uppercase;">Dismiss</button>
        </div>
    @endif

    @if($errors->any())
        <div class="flash-banner flash-banner--error" role="alert">
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <main>
        @yield('content')
    </main>

    @if(! View::hasSection('operator_shell'))
    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div class="brand-lockup brand-lockup--footer" style="margin-bottom:0.95rem;">
                        @include('partials.logo-seal', ['size' => 56, 'dark' => true])
                        <span class="brand-lockup__text">
                            <span class="brand-lockup__script">Café Isla<span class="brand-lockup__dot" aria-hidden="true">.</span></span>
                        </span>
                    </div>
                    <p class="footer-lead">Slow sips brewed from four Philippine shores—ceremonial matcha, dawn espresso, and wearable coastal calm.</p>
                    <form method="POST" action="{{ route('newsletter.store') }}" class="newsletter-form">
                        @csrf
                        <div class="footer-underline-form">
                            <input type="email" name="newsletter_email" value="{{ old('newsletter_email') }}" required maxlength="150" placeholder="EMAIL ADDRESS FOR SEASONAL POURS" aria-label="Email address for seasonal pours">
                            <button type="submit">Join List</button>
                        </div>
                        <label class="newsletter-consent">
                            <input type="checkbox" name="newsletter_consent" value="1" @checked(old('newsletter_consent')) required>
                            Send me Café Isla seasonal updates by email.
                        </label>
                        <a href="{{ route('privacy') }}">Newsletter privacy</a>
                    </form>
                </div>

                <div>
                    <h4 class="footer-heading">Island Chapters</h4>
                    <ul class="footer-links">
                        <li><a href="{{ route('menu.index', ['island' => 'la-union']) }}">La Union · Matcha &amp; Hojicha</a></li>
                        <li><a href="{{ route('menu.index', ['island' => 'siargao']) }}">Siargao · Reef Espresso</a></li>
                        <li><a href="{{ route('menu.index', ['island' => 'boracay']) }}">Boracay · Golden Hour Crème</a></li>
                        <li><a href="{{ route('menu.index', ['island' => 'el-nido']) }}">El Nido · Botanical Elixirs</a></li>
                        <li><a href="{{ route('menu.index', ['type' => 'merch']) }}">Isla Merch Capsule</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="footer-heading">Shop &amp; Support</h4>
                    <ul class="footer-links">
                        <li><a href="{{ route('cart.index') }}">Shopping Bag</a></li>
                        <li><a href="{{ route('orders.track') }}">Track Order</a></li>
                        <li><a href="{{ route('account.index') }}">My Account</a></li>
                        <li><a href="{{ route('delivery.policy') }}">Delivery &amp; Returns</a></li>
                        <li><a href="{{ route('contact') }}">Contact</a></li>
                        <li><a href="{{ route('privacy') }}">Privacy</a></li>
                    </ul>
                </div>
            </div>

            <div class="footer-bottom">
                <span>&copy; 2026 Café Isla &middot; Slow sips from the archipelago</span>
                <a href="#top" style="font-family:var(--font-sans);font-size:0.72rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;">Back To Top &uarr;</a>
            </div>
        </div>
    </footer>
    @endif

    <!-- Slide-Over Shopping Bag Drawer -->
    <div class="drawer-backdrop" id="bagDrawer" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Shopping Bag">
        <div class="drawer-panel">
            <div class="drawer-header">
                <h3 class="drawer-title">Your Isla Bag</h3>
                <button type="button" class="drawer-close" data-close-drawer>Close</button>
            </div>
            <div class="drawer-body" id="bagDrawerBody">
                <p style="font-family:var(--font-mono);font-size:0.84rem;color:var(--taupe-muted);">Loading your bag...</p>
            </div>
            <div class="drawer-footer" id="bagDrawerFooter"></div>
        </div>
    </div>

    <!-- Slide-Over Live Dual-Channel Email Inbox Drawer -->
    @auth
    <div class="drawer-backdrop" id="inboxDrawer" data-inbox-url="{{ route('inbox.index') }}" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Transactional Email Inbox">
        <div class="drawer-panel">
            <div class="drawer-header">
                <h3 class="drawer-title">Live Email Dispatch</h3>
                <button type="button" class="drawer-close" data-close-drawer>Close</button>
            </div>
            <div class="drawer-body" id="inboxDrawerBody">
                <p style="font-family:var(--font-mono);font-size:0.84rem;color:var(--taupe-muted);">Fetching logged confirmation, shipping, and refund notices...</p>
            </div>
            <div class="drawer-footer" style="font-family:var(--font-mono);font-size:0.75rem;color:var(--taupe-muted);">
                Order receipts and updates for your account.
            </div>
        </div>
    </div>
    @endauth

    <script src="{{ asset('assets/js/isla.js') }}"></script>
    @stack('scripts')
</body>
</html>
