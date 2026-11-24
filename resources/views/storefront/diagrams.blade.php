@extends('layouts.app')

@section('title', 'ERD, Use Cases & Brand Defense · Café Isla')

@section('content')
    <section class="admin-shell-bar">
        <div class="container">
            <div class="admin-shell-bar__top">
                <div>
                    <h1 class="admin-shell-bar__title">PCU Defense Presentation Diagrams &amp; Brand Dossier</h1>
                    <div style="font-family:var(--font-mono);font-size:0.82rem;opacity:0.82;margin-top:0.25rem;">
                        16-Table Normalized ERD (10 pts) &middot; 5-Actor / 7-Stage UML Use Case Diagram (15 pts) &middot; Brand Name &amp; Logo Rationale (20 pts)
                    </div>
                </div>
                <button type="button" onclick="window.print()" class="tag-btn tag-btn--sm">Print / Save PDF</button>
            </div>

            <div class="admin-tabs" role="tablist">
                <button type="button" class="admin-tab is-active" data-diagram-tab="erd" style="cursor:pointer;">1. Entity Relationship Diagram (16 Tables)</button>
                <button type="button" class="admin-tab" data-diagram-tab="usecase" style="cursor:pointer;">2. UML Use Case Diagram (5 Actors · 7 Stages)</button>
                <button type="button" class="admin-tab" data-diagram-tab="brand" style="cursor:pointer;">3. Brand, Logo &amp; Demo Credentials</button>
            </div>
        </div>
    </section>

    <section class="container operate-main">
        <!-- TAB 1: ERD -->
        <div data-diagram-panel="erd" style="display:block;">
            <div class="diagram-canvas">
                <h2 class="ledger-panel__title">Normalized Relational Schema (`cafe_isla_db` &middot; MySQL InnoDB)</h2>
                <p style="font-family:var(--font-mono);font-size:0.82rem;color:var(--taupe-muted);margin-bottom:1.25rem;">
                    16 tables with strict Primary Keys (PK), Unique Keys (UK), Foreign Keys (FK), and snapshot integrity on <code>order_items</code>.
                </p>

                <div class="erd-grid">
                    @php
                        $entities = [
                            ['name' => 'users', 'tag' => 'RBAC', 'cols' => [['id', 'PK'], ['name', 'varchar'], ['email', 'UK'], ['phone', 'varchar'], ['password', 'bcrypt'], ['role', 'enum(4)'], ['is_active', 'bool']]],
                            ['name' => 'user_addresses', 'tag' => '1:N users', 'cols' => [['id', 'PK'], ['user_id', 'FK'], ['label', 'varchar'], ['street_address', 'varchar'], ['city', 'varchar'], ['delivery_zone', 'enum']]],
                            ['name' => 'island_series', 'tag' => '4 Shores', 'cols' => [['id', 'PK'], ['code', 'UK'], ['name', 'varchar'], ['slug', 'UK'], ['coordinates', 'varchar'], ['accent_hex', 'varchar']]],
                            ['name' => 'categories', 'tag' => 'Catalog', 'cols' => [['id', 'PK'], ['name', 'varchar'], ['slug', 'UK'], ['product_type', 'drink|merch'], ['is_archived', 'bool']]],
                            ['name' => 'products', 'tag' => '25 Items', 'cols' => [['id', 'PK'], ['island_series_id', 'FK'], ['category_id', 'FK'], ['sku', 'UK'], ['slug', 'UK'], ['is_perishable', 'bool'], ['base_price', 'decimal']]],
                            ['name' => 'product_variants', 'tag' => '42 SKUs', 'cols' => [['id', 'PK'], ['product_id', 'FK'], ['variant_code', 'UK'], ['serve_temp', 'hot|iced|na'], ['price', 'decimal'], ['stock_qty', 'int']]],
                            ['name' => 'coupons', 'tag' => 'Promos', 'cols' => [['id', 'PK'], ['code', 'UK'], ['discount_type', 'enum'], ['discount_value', 'decimal'], ['min_subtotal', 'decimal'], ['is_active', 'bool']]],
                            ['name' => 'carts', 'tag' => 'Session', 'cols' => [['id', 'PK'], ['user_id', 'FK?'], ['session_token', 'UK'], ['coupon_id', 'FK?'], ['status', 'enum']]],
                            ['name' => 'cart_items', 'tag' => '1:N carts', 'cols' => [['id', 'PK'], ['cart_id', 'FK'], ['product_variant_id', 'FK'], ['quantity', 'int'], ['unit_price', 'decimal'], ['custom_notes', 'varchar']]],
                            ['name' => 'orders', 'tag' => 'Core TX', 'cols' => [['id', 'PK'], ['order_number', 'UK'], ['user_id', 'FK'], ['coupon_id', 'FK?'], ['delivery_zone', 'enum'], ['grand_total', 'decimal'], ['status', 'enum']]],
                            ['name' => 'order_items', 'tag' => 'Snapshot', 'cols' => [['id', 'PK'], ['order_id', 'FK'], ['product_id', 'FK'], ['product_variant_id', 'FK'], ['is_perishable', 'bool'], ['line_total', 'decimal'], ['custom_notes', 'string']]],
                            ['name' => 'payments', 'tag' => '1:1 orders', 'cols' => [['id', 'PK'], ['order_id', 'UK, FK'], ['method', 'gcash|cod'], ['reference_number', 'varchar'], ['status', 'enum']]],
                            ['name' => 'deliveries', 'tag' => '1:1 orders', 'cols' => [['id', 'PK'], ['order_id', 'UK, FK'], ['courier_user_id', 'FK?'], ['tracking_number', 'UK'], ['status', 'enum']]],
                            ['name' => 'return_requests', 'tag' => 'Hybrid', 'cols' => [['id', 'PK'], ['order_id', 'FK'], ['order_item_id', 'FK?'], ['user_id', 'FK'], ['processed_by', 'FK?'], ['restock_applied', 'bool'], ['status', 'enum']]],
                            ['name' => 'email_logs', 'tag' => 'Dual-Mail', 'cols' => [['id', 'PK'], ['user_id', 'FK?'], ['order_id', 'FK?'], ['event_type', 'enum'], ['recipient_email', 'varchar'], ['smtp_status', 'varchar']]],
                            ['name' => 'shop_visits', 'tag' => 'Funnel', 'cols' => [['id', 'PK'], ['session_token', 'idx'], ['user_id', 'FK?'], ['had_cart', 'bool'], ['converted_order_id', 'FK?']]],
                        ];
                    @endphp

                    @foreach($entities as $entity)
                        <div class="erd-entity">
                            <div class="erd-entity__head">
                                <span>{{ $entity['name'] }}</span>
                                <span style="opacity:0.78;font-family:var(--font-mono);font-size:0.68rem;">{{ $entity['tag'] }}</span>
                            </div>
                            <ul class="erd-entity__cols">
                                @foreach($entity['cols'] as $col)
                                    <li>
                                        <span>{{ $col[0] }}</span>
                                        <span class="{{ in_array($col[1], ['PK', 'FK', 'FK?', 'UK', 'UK, FK'], true) ? 'erd-key' : '' }}">{{ $col[1] }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                <h3 style="margin:1.75rem 0 0.75rem 0;font-family:var(--font-serif);font-style:italic;color:var(--canopy-deep);">Key Cardinality Relationships</h3>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Parent Entity</th>
                                <th>Cardinality</th>
                                <th>Child Entity</th>
                                <th>Foreign Key &amp; Business Meaning</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr><td>users</td><td class="erd-key">1 : N (0..*)</td><td>user_addresses, orders, return_requests, email_logs</td><td>A customer saves multiple delivery addresses, places many orders, and submits return/cancellation claims.</td></tr>
                            <tr><td>users (courier)</td><td class="erd-key">1 : N (0..*)</td><td>deliveries</td><td>`deliveries.courier_user_id` links an assigned rider (`courier`) to fulfilled orders.</td></tr>
                            <tr><td>island_series / categories</td><td class="erd-key">1 : N (1..*)</td><td>products</td><td>Every beverage or Isla Merch SKU belongs to one Island Series and one Category.</td></tr>
                            <tr><td>products</td><td class="erd-key">1 : N (1..*)</td><td>product_variants</td><td>Each drink or garment branches into Hot/Iced sizes or apparel sizes (`stock_qty` tracked per variant).</td></tr>
                            <tr><td>orders</td><td class="erd-key">1 : N (1..*)</td><td>order_items</td><td>Order snapshots preserve pricing and `is_perishable` truth even if menu prices change later.</td></tr>
                            <tr><td>orders</td><td class="erd-key">1 : 1</td><td>payments / deliveries</td><td>Unique FK constraints guarantee exactly one settlement record and one dispatch tracker per order.</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- TAB 2: UML USE CASE DIAGRAM -->
        <div data-diagram-panel="usecase" style="display:none;">
            <div class="diagram-canvas">
                <h2 class="ledger-panel__title">UML Use Case Diagram — All 5 Actors &amp; 7 Order Lifecycle Stages</h2>
                <p style="font-family:var(--font-mono);font-size:0.82rem;color:var(--taupe-muted);margin-bottom:1.25rem;">
                    Includes explicit dashed <code>&lt;&lt;include&gt;&gt;</code> (stock, address, payment validation) and <code>&lt;&lt;extend&gt;&gt;</code> (apply coupon, restock non-perishable merch) relationships.
                </p>

                <svg class="usecase-svg" viewBox="0 0 1120 760" xmlns="http://www.w3.org/2000/svg" font-family="Archivo, sans-serif">
                    <!-- System Boundary -->
                    <rect x="210" y="20" width="700" height="720" fill="#FBF8F1" stroke="#1C3127" stroke-width="2"/>
                    <text x="560" y="48" text-anchor="middle" font-family="Libre Caslon Text, serif" font-style="italic" font-size="20" fill="#1C3127">Café Isla Web Application System Boundary</text>

                    <!-- Left Actors: Customer & Courier -->
                    <g stroke="#261D18" stroke-width="2" fill="none">
                        <circle cx="85" cy="180" r="18"/>
                        <line x1="85" y1="198" x2="85" y2="245"/>
                        <line x1="58" y1="215" x2="112" y2="215"/>
                        <line x1="85" y1="245" x2="62" y2="280"/>
                        <line x1="85" y1="245" x2="108" y2="280"/>
                    </g>
                    <text x="85" y="302" text-anchor="middle" font-size="13" font-weight="700" fill="#261D18">CUSTOMER</text>
                    <text x="85" y="318" text-anchor="middle" font-size="11" fill="#6E6256">(Registered / Guest)</text>

                    <g stroke="#261D18" stroke-width="2" fill="none">
                        <circle cx="85" cy="520" r="18"/>
                        <line x1="85" y1="538" x2="85" y2="585"/>
                        <line x1="58" y1="555" x2="112" y2="555"/>
                        <line x1="85" y1="585" x2="62" y2="620"/>
                        <line x1="85" y1="585" x2="108" y2="620"/>
                    </g>
                    <text x="85" y="642" text-anchor="middle" font-size="13" font-weight="700" fill="#261D18">COURIER</text>
                    <text x="85" y="658" text-anchor="middle" font-size="11" fill="#6E6256">(Fulfillment Rider)</text>

                    <!-- Right Actors: Admin & External Systems -->
                    <g stroke="#261D18" stroke-width="2" fill="none">
                        <circle cx="1025" cy="160" r="18"/>
                        <line x1="1025" y1="178" x2="1025" y2="225"/>
                        <line x1="998" y1="195" x2="1052" y2="195"/>
                        <line x1="1025" y1="225" x2="1002" y2="260"/>
                        <line x1="1025" y1="225" x2="1048" y2="260"/>
                    </g>
                    <text x="1025" y="282" text-anchor="middle" font-size="13" font-weight="700" fill="#261D18">ADMIN</text>
                    <text x="1025" y="298" text-anchor="middle" font-size="11" fill="#6E6256">(Store Owner)</text>

                    <rect x="945" y="385" width="160" height="58" fill="#ECE3D0" stroke="#1C3127" stroke-width="1.5"/>
                    <text x="1025" y="408" text-anchor="middle" font-size="10" fill="#C4553A" font-weight="700">&lt;&lt;external actor&gt;&gt;</text>
                    <text x="1025" y="428" text-anchor="middle" font-size="12" font-weight="700" fill="#261D18">PAYMENT GATEWAY</text>

                    <rect x="945" y="535" width="160" height="58" fill="#ECE3D0" stroke="#1C3127" stroke-width="1.5"/>
                    <text x="1025" y="558" text-anchor="middle" font-size="10" fill="#C4553A" font-weight="700">&lt;&lt;external actor&gt;&gt;</text>
                    <text x="1025" y="578" text-anchor="middle" font-size="12" font-weight="700" fill="#261D18">EMAIL SERVICE</text>

                    <!-- Use Case Ovals (Stage Labels + Ovals) -->
                    <!-- Stage 1 & 2 -->
                    <ellipse cx="340" cy="100" rx="95" ry="24" fill="#F6F1E6" stroke="#1C3127" stroke-width="1.5"/>
                    <text x="340" y="104" text-anchor="middle" font-size="12" fill="#261D18">Browse, Search &amp; Filter</text>

                    <ellipse cx="340" cy="162" rx="95" ry="24" fill="#F6F1E6" stroke="#1C3127" stroke-width="1.5"/>
                    <text x="340" y="166" text-anchor="middle" font-size="12" fill="#261D18">Register, Login &amp; Profile</text>

                    <!-- Stage 3: Buying -->
                    <ellipse cx="340" cy="235" rx="95" ry="24" fill="#F6F1E6" stroke="#1C3127" stroke-width="1.5"/>
                    <text x="340" y="239" text-anchor="middle" font-size="12" fill="#261D18">Manage Persistent Bag</text>

                    <ellipse cx="340" cy="310" rx="95" ry="24" fill="#F6F1E6" stroke="#1C3127" stroke-width="1.5"/>
                    <text x="340" y="314" text-anchor="middle" font-size="12" font-weight="700" fill="#1C3127">Checkout &amp; Place Order</text>

                    <ellipse cx="620" cy="218" rx="85" ry="21" fill="#ECE3D0" stroke="#C4553A" stroke-width="1.4"/>
                    <text x="620" y="222" text-anchor="middle" font-size="11" fill="#261D18">Apply Promo Coupon</text>

                    <ellipse cx="620" cy="276" rx="88" ry="21" fill="#ECE3D0" stroke="#1C3127" stroke-width="1.4"/>
                    <text x="620" y="280" text-anchor="middle" font-size="11" fill="#261D18">Validate Variant Stock</text>

                    <ellipse cx="620" cy="332" rx="88" ry="21" fill="#ECE3D0" stroke="#1C3127" stroke-width="1.4"/>
                    <text x="620" y="336" text-anchor="middle" font-size="11" fill="#261D18">Validate Shipping Address</text>

                    <ellipse cx="620" cy="390" rx="88" ry="22" fill="#ECE3D0" stroke="#1C3127" stroke-width="1.4"/>
                    <text x="620" y="394" text-anchor="middle" font-size="11" fill="#261D18">Process Payment</text>

                    <!-- Stage 4 & 5: Processing & Delivery -->
                    <ellipse cx="620" cy="462" rx="98" ry="22" fill="#F6F1E6" stroke="#1C3127" stroke-width="1.5"/>
                    <text x="620" y="466" text-anchor="middle" font-size="11" fill="#261D18">Confirm, Pack &amp; Assign Courier</text>

                    <ellipse cx="340" cy="540" rx="95" ry="24" fill="#F6F1E6" stroke="#1C3127" stroke-width="1.5"/>
                    <text x="340" y="544" text-anchor="middle" font-size="12" fill="#261D18">Ship, Track &amp; Confirm Delivery</text>

                    <!-- Stage 6: Returns & Refunds -->
                    <ellipse cx="340" cy="618" rx="95" ry="24" fill="#F6F1E6" stroke="#1C3127" stroke-width="1.5"/>
                    <text x="340" y="622" text-anchor="middle" font-size="11" fill="#261D18">Request Cancel / Return</text>

                    <ellipse cx="620" cy="618" rx="98" ry="24" fill="#F6F1E6" stroke="#1C3127" stroke-width="1.5"/>
                    <text x="620" y="622" text-anchor="middle" font-size="11" fill="#261D18">Approve Return &amp; Refund</text>

                    <ellipse cx="620" cy="692" rx="108" ry="22" fill="#ECE3D0" stroke="#C4553A" stroke-width="1.4"/>
                    <text x="620" y="696" text-anchor="middle" font-size="11" fill="#261D18">Restock Non-Perishable Merch</text>

                    <!-- Stage 7: Admin Management -->
                    <ellipse cx="770" cy="112" rx="115" ry="24" fill="#F6F1E6" stroke="#1C3127" stroke-width="1.5"/>
                    <text x="770" y="116" text-anchor="middle" font-size="11" fill="#261D18">CRUD Products, Uploads &amp; RBAC</text>

                    <!-- Solid Association Lines -->
                    <g stroke="#261D18" stroke-width="1.3">
                        <line x1="115" y1="205" x2="245" y2="100"/>
                        <line x1="115" y1="210" x2="245" y2="162"/>
                        <line x1="115" y1="220" x2="245" y2="235"/>
                        <line x1="115" y1="228" x2="245" y2="310"/>
                        <line x1="115" y1="245" x2="245" y2="540"/>
                        <line x1="115" y1="260" x2="245" y2="618"/>

                        <line x1="115" y1="545" x2="245" y2="540"/>

                        <line x1="995" y1="185" x2="885" y2="112"/>
                        <line x1="995" y1="210" x2="718" y2="462"/>
                        <line x1="995" y1="230" x2="718" y2="618"/>

                        <line x1="708" y1="390" x2="945" y2="414"/>
                        <line x1="718" y1="618" x2="945" y2="425"/>
                        <line x1="435" y1="310" x2="945" y2="555"/>
                        <line x1="718" y1="462" x2="945" y2="564"/>
                        <line x1="718" y1="618" x2="945" y2="574"/>
                    </g>

                    <!-- Dashed <<include>> and <<extend>> Arrows -->
                    <g stroke="#C4553A" stroke-width="1.3" stroke-dasharray="5,4">
                        <line x1="535" y1="218" x2="435" y2="235"/>
                        <line x1="435" y1="305" x2="532" y2="276"/>
                        <line x1="435" y1="315" x2="532" y2="332"/>
                        <line x1="435" y1="322" x2="532" y2="385"/>
                        <line x1="620" y1="670" x2="620" y2="642"/>
                    </g>

                    <text x="486" y="216" text-anchor="middle" font-size="10" fill="#C4553A">&lt;&lt;extend&gt;&gt;</text>
                    <text x="488" y="280" text-anchor="middle" font-size="10" fill="#C4553A">&lt;&lt;include&gt;&gt;</text>
                    <text x="490" y="346" text-anchor="middle" font-size="10" fill="#C4553A">&lt;&lt;include&gt;&gt;</text>
                    <text x="482" y="378" text-anchor="middle" font-size="10" fill="#C4553A">&lt;&lt;include&gt;&gt;</text>
                    <text x="668" y="660" text-anchor="middle" font-size="10" fill="#C4553A">&lt;&lt;extend&gt;&gt;</text>
                </svg>
            </div>
        </div>

        <!-- TAB 3: BRAND, LOGO & DEMO CREDENTIALS -->
        <div data-diagram-panel="brand" style="display:none;">
            <div class="operate-grid-even">
                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Brand Name &amp; Logo Defense Notes (20 Points)</h2>
                    <div class="form-stack">
                        <div>
                            <strong style="font-family:var(--font-sans);font-size:0.78rem;text-transform:uppercase;">Brand Meaning — Café Isla</strong>
                            <p style="margin:0.35rem 0 0 0;">
                                Evolved from our coastal apparel showcase <em>Suot-Isla</em>, <strong>Café Isla</strong> translates four Philippine island shores (<strong>La Union</strong>, <strong>Siargao</strong>, <strong>Boracay</strong>, and <strong>El Nido</strong>) into small-batch ceremonial matcha, roasted hojicha, reef espresso, botanical infusions, and wearable coastal essentials.
                            </p>
                        </div>

                        <div>
                            <strong style="font-family:var(--font-sans);font-size:0.78rem;text-transform:uppercase;">Full Brand Identity &amp; Roastery Seal Specimens</strong>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.85rem;margin-top:0.6rem;">
                                <div style="padding:1.25rem;border:1px solid var(--espresso-ink);background:var(--bg-parchment);display:flex;flex-direction:column;align-items:center;text-align:center;gap:0.65rem;">
                                    <div id="sealLightWrap">@include('partials.logo-seal', ['size' => 124])</div>
                                    <div>
                                        <span style="font-family:'Pinyon Script',Georgia,cursive;font-size:2.1rem;color:#1C3127;line-height:0.92;">Café Isla</span>
                                        <div style="font-family:var(--font-serif);font-style:italic;font-size:0.74rem;color:#6E6256;margin-top:3px;">Parchment Cup-Sleeve &amp; Storefront Seal</div>
                                    </div>
                                    <button type="button" class="tag-btn tag-btn--outline tag-btn--sm" data-download-seal="sealLightWrap" data-filename="cafe-isla-seal-parchment.svg">Download SVG</button>
                                </div>
                                <div style="padding:1.25rem;border:1px solid var(--espresso-ink);background:#1C3127;display:flex;flex-direction:column;align-items:center;text-align:center;gap:0.65rem;">
                                    <div id="sealDarkWrap">@include('partials.logo-seal', ['size' => 124, 'dark' => true])</div>
                                    <div>
                                        <span style="font-family:'Pinyon Script',Georgia,cursive;font-size:2.1rem;color:#F6F1E6;line-height:0.92;">Café Isla</span>
                                        <div style="font-family:var(--font-serif);font-style:italic;font-size:0.74rem;color:rgba(246,241,230,0.78);margin-top:3px;">Inverted Canopy Merch Tag &amp; Footer Seal</div>
                                    </div>
                                    <button type="button" class="tag-btn tag-btn--sm" data-download-seal="sealDarkWrap" data-filename="cafe-isla-seal-canopy.svg">Download SVG</button>
                                </div>
                            </div>
                            <p style="margin:0.65rem 0 0 0;">
                                The double-ring <strong>Coastal Sun, Leaf &amp; Horizon</strong> roastery seal frames curved circular typography (<code>CAFÉ ISLA · ARCHIPELAGO · 2026</code>) around a terracotta rising sun with dawn rays (<code>#C4553A</code>), a botanical tea/palm leaf silhouette, and dual ocean waves in Ceremonial Canopy Green (<code>#1C3127</code>) over Abaca Parchment (<code>#F6F1E6</code>).
                            </p>
                        </div>

                        <div>
                            <strong style="font-family:var(--font-sans);font-size:0.78rem;text-transform:uppercase;">Packaging &amp; Merch Application Preview</strong>
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:0.85rem;margin-top:0.55rem;">
                                <div style="border:1px solid var(--espresso-ink);background:#ECE3D0;padding:1rem;display:flex;align-items:center;gap:0.9rem;">
                                    @include('partials.logo-seal', ['size' => 58])
                                    <div style="font-family:var(--font-mono);font-size:0.74rem;line-height:1.45;">
                                        <strong style="font-family:var(--font-sans);text-transform:uppercase;letter-spacing:0.06em;">Kraft Cup Sleeve</strong><br>
                                        La Union Matcha Bar<br>
                                        16.6631° N, 120.3208° E
                                    </div>
                                </div>
                                <div style="border:1px solid var(--espresso-ink);background:#1C3127;color:#F6F1E6;padding:1rem;display:flex;align-items:center;gap:0.9rem;">
                                    @include('partials.logo-seal', ['size' => 58, 'dark' => true])
                                    <div style="font-family:var(--font-mono);font-size:0.74rem;line-height:1.45;">
                                        <strong style="font-family:var(--font-sans);text-transform:uppercase;letter-spacing:0.06em;">Isla Merch Hangtag</strong><br>
                                        Suot-Isla Coastal Linen<br>
                                        Batch · Small-Run Drop
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div>
                            <strong style="font-family:var(--font-sans);font-size:0.78rem;text-transform:uppercase;">Brand Palette Swatches (Click to Copy Hex)</strong>
                            <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:0.45rem;margin-top:0.55rem;">
                                @foreach([['#F6F1E6','Parchment','#261D18'],['#ECE3D0','Limestone','#261D18'],['#1C3127','Canopy','#F6F1E6'],['#C4553A','Clay Coral','#F6F1E6'],['#4A6B46','La Union','#F6F1E6'],['#1F4E52','Siargao','#F6F1E6'],['#B87B28','Boracay','#F6F1E6'],['#335C6E','El Nido','#F6F1E6']] as $sw)
                                    <button type="button" data-copy-hex="{{ $sw[0] }}" style="border:1px solid var(--espresso-ink);background:{{ $sw[0] }};color:{{ $sw[2] }};padding:0.5rem 0.4rem;text-align:left;cursor:pointer;font-family:var(--font-mono);font-size:0.7rem;line-height:1.3;">
                                        <strong>{{ $sw[1] }}</strong><br>{{ $sw[0] }}
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        <div>
                            <strong style="font-family:var(--font-sans);font-size:0.78rem;text-transform:uppercase;">Three-Voice Typography</strong>
                            <ul style="margin:0.35rem 0 0 0;padding-left:1.15rem;font-family:var(--font-mono);font-size:0.82rem;">
                                <li><strong>Libre Caslon Display &amp; Text</strong>: Editorial italic display &amp; warm story copy</li>
                                <li><strong>Archivo</strong>: Tracked uppercase UI navigation, drink titles &amp; rectangular tag buttons</li>
                                <li><strong>Courier Prime</strong>: Coordinates, tasting notes, batch stock &amp; tabular peso figures</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Live Demonstration Accounts &amp; Rubric Matrix</h2>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Role</th>
                                    <th>Email &amp; Password</th>
                                    <th>Portal Scope</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td><strong>Customer</strong></td><td style="font-family:var(--font-mono);">customer@cafeisla.ph<br>Isla2026!</td><td>Browse, Bag, Checkout, Track, Inbox, Cancel &amp; Return</td></tr>
                                <tr><td><strong>Admin</strong></td><td style="font-family:var(--font-mono);">admin@cafeisla.ph<br>Isla2026!</td><td>Full CRUD + Image Upload, Analytics, Orders, Returns, Team RBAC</td></tr>
                                <tr><td><strong>Courier</strong></td><td style="font-family:var(--font-mono);">rider@cafeisla.ph<br>Isla2026!</td><td>Fulfillment-Only: Courier Tracking &rarr; Confirm Delivery &rarr; Delivered</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:1.15rem;font-family:var(--font-mono);font-size:0.8rem;color:var(--taupe-muted);">
                        <strong>Promo Codes for Demo:</strong> <code>ISLA10</code> (10% off min ₱300) &middot; <code>FREESIP</code> (Free local delivery over ₱500).
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
