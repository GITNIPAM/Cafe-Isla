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
    <link href="https://fonts.googleapis.com/css2?family=Archivo:wght@500;600;700&family=Courier+Prime:wght@400;700&family=Libre+Caslon+Display&family=Libre+Caslon+Text:ital,wght@0,400;0,700;1,400&family=Pinyon+Script&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/isla.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/operate.css') }}">
    @stack('styles')
</head>
<body id="top">
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
                @include('partials.logo-seal', ['size' => 52, 'class' => 'brand-lockup__seal'])
                <span class="brand-lockup__text">
                    <span class="brand-lockup__script">Café Isla</span>
                    <span class="brand-lockup__tagline">Slow sips from the archipelago</span>
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
                <a href="{{ route('diagrams.index') }}" class="site-nav__link {{ request()->routeIs('diagrams.index') ? 'is-active' : '' }}">Diagrams</a>
            </nav>

            <div class="site-actions">
                <a href="{{ route('menu.index') }}#searchBox" class="header-action-btn" title="Search Menu & Merch">
                    <svg width="14" height="14" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <circle cx="7" cy="7" r="5"/>
                        <path d="M11 11L14.5 14.5"/>
                    </svg>
                    <span>Search</span>
                </a>

                <button type="button" class="header-action-btn" data-open-inbox>
                    <svg width="15" height="14" viewBox="0 0 16 14" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                        <rect x="1" y="1.5" width="14" height="11"/>
                        <path d="M1 2.5L8 8L15 2.5"/>
                    </svg>
                    <span>Inbox</span>
                    <span class="header-action-count tabular">{{ isset($sharedEmailLogs) ? $sharedEmailLogs->count() : 0 }}</span>
                </button>

                @auth
                    @if(auth()->user()->isOperator())
                        <a href="{{ route('admin.index') }}" class="header-action-btn">Portal ({{ ucfirst(auth()->user()->role) }})</a>
                    @else
                        <a href="{{ route('account.index') }}" class="header-action-btn">Account</a>
                    @endif
                @else
                    <a href="{{ route('auth.index') }}" class="header-action-btn">Sign In</a>
                @endauth

                <button type="button" class="header-action-btn" data-open-bag>
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

    <footer class="site-footer">
        <div class="container">
            <div class="footer-grid">
                <div>
                    <div style="margin-bottom:0.9rem;">
                        @include('partials.logo-seal', ['size' => 68, 'dark' => true])
                    </div>
                    <p class="footer-lead">Slow sips brewed from four Philippine shores—ceremonial matcha, dawn espresso, and wearable coastal calm.</p>
                    <form class="footer-underline-form" onsubmit="event.preventDefault(); alert('Salamat! You are on the Café Isla seasonal island harvest dispatch.'); this.reset();">
                        <input type="email" required placeholder="EMAIL ADDRESS FOR SEASONAL POURS" aria-label="Email address for seasonal pours">
                        <button type="submit">Join List</button>
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
                    <h4 class="footer-heading">Storefront &amp; Defense</h4>
                    <ul class="footer-links">
                        <li><a href="{{ route('cart.index') }}">Persistent Shopping Bag</a></li>
                        <li><a href="{{ route('orders.track') }}">Track Order Status</a></li>
                        <li><a href="{{ route('account.index') }}">Customer Dashboard &amp; Returns</a></li>
                        <li><a href="{{ route('admin.index') }}">Admin &amp; Courier Portal</a></li>
                        <li><a href="{{ route('diagrams.index') }}">ERD, Use Cases &amp; Brand Guide</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="footer-heading">Coastal Bar Hours</h4>
                    <p style="margin:0;font-family:var(--font-mono);font-size:0.8rem;line-height:1.65;color:rgba(246,241,230,0.84);">
                        San Juan, La Union &middot; 16.6631° N<br>
                        Daily 6:30 AM &ndash; 7:00 PM<br>
                        Dasmariñas &amp; Metro Express Rider<br>
                        hello@cafeisla.ph
                    </p>
                    <p style="margin-top:1.1rem;">
                        <a href="#top" style="font-family:var(--font-sans);font-size:0.72rem;font-weight:700;letter-spacing:0.1em;text-transform:uppercase;">Back To Top &uarr;</a>
                    </p>
                </div>
            </div>

            <div class="footer-bottom">
                <span>Café Isla &middot; Slow sips from the archipelago (Evolved from Suot-Isla)</span>
                <span>Philippine Christian University &middot; College of Informatics Final Project</span>
            </div>
        </div>
    </footer>

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
    <div class="drawer-backdrop" id="inboxDrawer" aria-hidden="true" role="dialog" aria-modal="true" aria-label="Transactional Email Inbox">
        <div class="drawer-panel">
            <div class="drawer-header">
                <h3 class="drawer-title">Live Email Dispatch</h3>
                <button type="button" class="drawer-close" data-close-drawer>Close</button>
            </div>
            <div class="drawer-body" id="inboxDrawerBody">
                <p style="font-family:var(--font-mono);font-size:0.84rem;color:var(--taupe-muted);">Fetching logged confirmation, shipping, and refund notices...</p>
            </div>
            <div class="drawer-footer" style="font-family:var(--font-mono);font-size:0.75rem;color:var(--taupe-muted);">
                Dual-channel proof: dispatches SMTP when configured and preserves live HTML notice previews in <code>email_logs</code>.
            </div>
        </div>
    </div>

    <script src="{{ asset('assets/js/isla.js') }}"></script>
    @stack('scripts')
</body>
</html>
