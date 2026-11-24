@extends('layouts.app')

@section('title', $role === 'courier' ? 'Rider Dispatch · Café Isla' : 'Operator Portal · Café Isla')
@section('operator_shell', true)

@section('content')
    @php
        $sectionHeading = match ($activeTab) {
            'analytics' => 'Reporting & Analytics',
            'inventory' => 'Inventory & Catalog',
            'returns' => 'Returns & Refunds',
            'team' => 'Customers & RBAC',
            default => $role === 'courier' ? 'Assigned Deliveries' : 'Orders & Fulfillment',
        };
    @endphp

    <div class="operator-shell">
        <aside class="operator-sidebar {{ $role === 'courier' ? 'operator-sidebar--courier' : '' }}" aria-label="Operator Navigation">
            <div>
                <div class="operator-brand">
                    @include('partials.logo-seal', ['size' => 42, 'dark' => true])
                    <div>
                        <h1 class="operator-brand__title">
                            {{ $role === 'courier' ? 'Rider Dispatch' : 'Café Isla Ops' }}
                        </h1>
                        <div class="operator-brand__sub">
                            {{ $role === 'courier' ? 'Fulfillment Only' : 'Admin Console' }}
                        </div>
                    </div>
                </div>

                <nav class="operator-nav" aria-label="Portal Workspace Tabs">
                    @if($role === 'admin')
                        <a href="{{ route('admin.index', ['tab' => 'analytics']) }}" class="operator-nav__link {{ $activeTab === 'analytics' ? 'is-active' : '' }}">
                            <span>Analytics</span>
                        </a>
                        <a href="{{ route('admin.index', ['tab' => 'orders']) }}" class="operator-nav__link {{ $activeTab === 'orders' ? 'is-active' : '' }}">
                            <span>Orders</span>
                            <span class="tabular">{{ $orders->count() }}</span>
                        </a>
                        <a href="{{ route('admin.index', ['tab' => 'inventory']) }}" class="operator-nav__link {{ $activeTab === 'inventory' ? 'is-active' : '' }}">
                            <span>Inventory</span>
                            <span class="tabular">{{ $products->count() }}</span>
                        </a>
                        <a href="{{ route('admin.index', ['tab' => 'returns']) }}" class="operator-nav__link {{ $activeTab === 'returns' ? 'is-active' : '' }}">
                            <span>Returns</span>
                            <span class="tabular">{{ $returns->count() }}</span>
                        </a>
                        <a href="{{ route('admin.index', ['tab' => 'team']) }}" class="operator-nav__link {{ $activeTab === 'team' ? 'is-active' : '' }}">
                            <span>Customers &amp; RBAC</span>
                        </a>
                    @else
                        <a href="{{ route('admin.index', ['tab' => 'orders']) }}" class="operator-nav__link is-active">
                            <span>Assigned Deliveries</span>
                            <span class="tabular">{{ $orders->count() }}</span>
                        </a>
                    @endif
                </nav>
            </div>

            <div class="operator-sidebar__footer">
                <a href="{{ route('home') }}" class="operator-storefront-btn">View Storefront &rarr;</a>
                <form method="POST" action="{{ route('auth.logout') }}">
                    @csrf
                    <button type="submit" class="tag-btn tag-btn--sm" style="width:100%;">Sign Out</button>
                </form>
            </div>
        </aside>

        <div class="operator-main">
            <header class="operator-topbar">
                <div>
                    <h2 class="operator-topbar__title">{{ $sectionHeading }}</h2>
                    <div class="operator-topbar__meta">
                        Signed in as {{ $user->name }} ({{ $user->email }}) &middot; {{ $role === 'courier' ? 'Courier' : 'Admin' }}
                    </div>
                </div>
                <div class="operator-topbar__actions">
                    <a href="{{ route('home') }}" class="tag-btn tag-btn--outline tag-btn--sm">Go to Storefront &rarr;</a>
                    <form method="POST" action="{{ route('auth.logout') }}">
                        @csrf
                        <button type="submit" class="tag-btn tag-btn--sm">Sign Out</button>
                    </form>
                </div>
            </header>

            <section class="operator-content">
        @if($activeTab === 'analytics' && $role === 'admin')
            <div class="kpi-strip">
                <div class="kpi-cell">
                    <div class="kpi-cell__label">Net Sales Revenue</div>
                    <div class="kpi-cell__value">₱{{ number_format($metrics['revenue'], 2) }}</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-cell__label">Total Orders</div>
                    <div class="kpi-cell__value">{{ $metrics['orders'] }}</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-cell__label">Active Queue</div>
                    <div class="kpi-cell__value">{{ $metrics['pending_orders'] }}</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-cell__label">Visit Conversion</div>
                    <div class="kpi-cell__value">{{ $metrics['conversion_rate'] }}%</div>
                </div>
                <div class="kpi-cell">
                    <div class="kpi-cell__label">Cart Abandonment</div>
                    <div class="kpi-cell__value" style="color:var(--clay-coral);">{{ $metrics['abandonment_rate'] }}%</div>
                </div>
            </div>

            <div class="operate-grid-even">
                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Top Performing Products</h2>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Units Sold</th>
                                    <th>Gross Sales</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($topProducts as $row)
                                    <tr>
                                        <td><strong>{{ $row->product_name_snapshot }}</strong></td>
                                        <td class="tabular" style="font-family:var(--font-mono);">{{ $row->units_sold }}</td>
                                        <td class="tabular" style="font-family:var(--font-mono);">₱{{ number_format((float) $row->gross_sales, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3">No sales recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div style="margin-top:1.15rem;font-family:var(--font-mono);font-size:0.85rem;color:var(--taupe-muted);">
                        Funnel telemetry: {{ $metrics['total_visits'] }} store visits &rarr; {{ $metrics['cart_visits'] }} bags started &rarr; {{ $metrics['converted_visits'] }} completed orders.
                    </div>
                </div>

                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Low-Stock Batch Alerts ({{ $lowStockVariants->count() }})</h2>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Product &amp; Variant</th>
                                    <th>Remaining</th>
                                    <th>Quick Restock</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($lowStockVariants as $lv)
                                    <tr>
                                        <td>
                                            <strong>{{ $lv->product->name }}</strong>
                                            <div style="font-size:0.85rem;color:var(--taupe-muted);">
                                                {{ $lv->label }} &middot; {{ $lv->product->islandSeries->name }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-tag status-tag--low tabular">{{ $lv->stock_qty }} left</span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.variants.update', $lv) }}" style="display:flex;gap:0.45rem;align-items:center;">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="price" value="{{ $lv->price }}">
                                                <input type="number" name="stock_qty" value="{{ $lv->stock_qty + 20 }}" min="0" class="field-control tabular" style="width:80px;padding:0.4rem 0.5rem;">
                                                <button type="submit" class="tag-btn tag-btn--canopy tag-btn--sm">Restock</button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3">All active variants are healthy above threshold.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        @elseif($activeTab === 'orders')
            @if($role === 'courier')
                <div class="kpi-strip kpi-strip--3">
                    <div class="kpi-cell">
                        <div class="kpi-cell__label">Assigned to You</div>
                        <div class="kpi-cell__value">{{ $orders->count() }}</div>
                    </div>
                    <div class="kpi-cell">
                        <div class="kpi-cell__label">In Transit</div>
                        <div class="kpi-cell__value">{{ $orders->where('status', 'shipped')->count() }}</div>
                    </div>
                    <div class="kpi-cell">
                        <div class="kpi-cell__label">Delivered</div>
                        <div class="kpi-cell__value">{{ $orders->where('status', 'delivered')->count() }}</div>
                    </div>
                </div>
            @endif

            <div class="ledger-panel">
                <h2 class="ledger-panel__title">
                    {{ $role === 'courier' ? 'Courier Dispatch & Delivery Updates' : 'Incoming Orders & Fulfillment Queue' }}
                </h2>

                <div class="admin-filter-bar">
                    <div class="admin-filter-pills" role="group" aria-label="Filter orders by status">
                        <button type="button" class="admin-filter-pill is-active" data-order-filter="all">All ({{ $orders->count() }})</button>
                        @foreach(['pending', 'confirmed', 'preparing', 'shipped', 'delivered', 'returned', 'cancelled'] as $filterStatus)
                            @php $statusCount = $orders->where('status', $filterStatus)->count(); @endphp
                            @if($statusCount > 0)
                                <button type="button" class="admin-filter-pill" data-order-filter="{{ $filterStatus }}">
                                    {{ ucfirst($filterStatus) }} ({{ $statusCount }})
                                </button>
                            @endif
                        @endforeach
                    </div>

                    <input type="search" id="adminOrderSearch" placeholder="Search order #, customer, or tracking #" class="field-control" style="max-width:300px;padding:0.5rem 0.75rem;">
                </div>

                @if($orders->isEmpty())
                    <p style="font-size:0.95rem;color:var(--taupe-muted);margin:0;">No orders in the queue yet.</p>
                @else
                    <div class="admin-card-list" id="adminOrderList">
                        @foreach($orders as $order)
                            @php
                                $delStatus = $order->delivery?->status ?? 'unassigned';
                                $stageIndex = match ($order->status) {
                                    'pending' => 0,
                                    'confirmed' => 1,
                                    'preparing' => 2,
                                    'shipped' => $delStatus === 'in_transit' ? 4 : 3,
                                    'delivered' => 6,
                                    default => -1,
                                };
                                $pipelineSteps = [
                                    1 => '1. Confirm',
                                    2 => '2. Preparing',
                                    3 => '3. Shipped',
                                    4 => '4. Tracking',
                                    5 => '5. Confirm Deliv',
                                    6 => '6. Delivered',
                                ];
                                $searchBlob = strtolower($order->order_number.' '.$order->shipping_recipient.' '.($order->delivery?->tracking_number ?? ''));
                            @endphp

                            <article class="admin-order-card" data-order-status="{{ $order->status }}" data-order-search="{{ $searchBlob }}">
                                <div class="admin-order-card__head">
                                    <div class="admin-order-card__id-group">
                                        <span class="admin-order-card__number">#{{ $order->order_number }}</span>
                                        <span class="admin-order-card__meta">{{ $order->placed_at->format('M d, Y · g:i A') }}</span>
                                        @if($order->delivery?->tracking_number)
                                            <a href="{{ route('orders.track', ['order' => $order->order_number]) }}" class="admin-order-card__meta" style="color:var(--canopy-deep);text-decoration:underline;text-underline-offset:3px;">
                                                {{ $order->delivery->tracking_number }}
                                            </a>
                                        @endif
                                    </div>
                                    <div class="admin-order-card__status-group">
                                        <span class="status-tag status-tag--{{ $order->status }}">{{ strtoupper($order->status) }}</span>
                                        <span class="admin-order-card__total tabular">₱{{ number_format((float) $order->grand_total, 2) }}</span>
                                    </div>
                                </div>

                                <div class="admin-order-card__body">
                                    <div>
                                        <div class="admin-section-label">Customer &amp; Delivery</div>
                                        <div class="admin-order-card__customer-name">{{ $order->shipping_recipient }}</div>
                                        <div class="admin-order-card__sub">
                                            {{ $order->shipping_phone }}<br>
                                            {{ $order->shipping_address_line }}, {{ $order->shipping_city }}, {{ $order->shipping_province }}
                                        </div>
                                        <div style="margin-top:0.6rem;font-family:var(--font-mono);font-size:0.82rem;color:var(--espresso-ink);">
                                            @if($role === 'courier')
                                                @if($order->payment?->method === 'cod' && $order->payment?->status !== 'paid')
                                                    <strong style="color:var(--clay-coral);">Collect Cash (COD): ₱{{ number_format((float) $order->grand_total, 2) }}</strong>
                                                @else
                                                    <strong style="color:var(--canopy-deep);">Prepaid ({{ $order->payment?->methodLabel() }}) &middot; ₱0.00 to collect</strong>
                                                @endif
                                                &middot; Delivery: {{ strtoupper($delStatus) }}
                                            @else
                                                Payment: {{ $order->payment?->methodLabel() }} ({{ strtoupper($order->payment?->status ?? 'pending') }})
                                                &middot; Delivery: {{ strtoupper($delStatus) }}
                                                @if($order->delivery?->courier)
                                                    &middot; Rider: {{ $order->delivery->courier->name }}
                                                @endif
                                            @endif
                                        </div>
                                    </div>

                                    <div>
                                        <div class="admin-section-label">Items Ordered</div>
                                        <ul class="order-item-list" style="margin:0;">
                                            @foreach($order->items as $item)
                                                <li class="order-item-row">
                                                    @if($item->product)
                                                        <img src="{{ $item->product->imageUrl() }}" alt="{{ $item->product_name_snapshot }}" class="order-item-thumb">
                                                    @endif
                                                    <div>
                                                        <div>{{ $item->quantity }}&times; {{ $item->product_name_snapshot }} ({{ $item->variant_label_snapshot }})</div>
                                                        @if($item->custom_notes)
                                                            <div style="font-size:0.82rem;color:var(--taupe-muted);">{{ $item->custom_notes }}</div>
                                                        @endif
                                                    </div>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>

                                <div class="admin-order-card__footer">
                                    @if($order->status === 'cancelled')
                                        <div style="font-size:0.9rem;color:var(--clay-coral);font-weight:600;">
                                            Order Cancelled &mdash; No further fulfillment steps.
                                        </div>
                                    @else
                                        <div class="admin-pipeline" aria-label="Fulfillment Pipeline">
                                            @foreach($pipelineSteps as $stepNum => $stepLabel)
                                                @php
                                                    $isDone = $stageIndex >= $stepNum;
                                                    $isNext = ($stageIndex + 1) === $stepNum;
                                                @endphp
                                                <div class="admin-pipeline__step {{ $isDone ? 'is-done' : ($isNext ? 'is-next' : '') }}">
                                                    {{ $stepLabel }}
                                                </div>
                                            @endforeach
                                        </div>

                                        @if($order->status === 'pending' && $role === 'admin')
                                            <div class="admin-action-bar">
                                                <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="confirmed">
                                                    <button type="submit" class="tag-btn tag-btn--canopy">1. Confirm Order &rarr;</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="submit" class="tag-btn tag-btn--outline">Cancel Order</button>
                                                </form>
                                            </div>
                                        @elseif($order->status === 'confirmed' && $role === 'admin')
                                            <div class="admin-action-bar">
                                                <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="preparing">
                                                    <button type="submit" class="tag-btn tag-btn--canopy">2. Start Preparing &rarr;</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="cancelled">
                                                    <button type="submit" class="tag-btn tag-btn--outline">Cancel Order</button>
                                                </form>
                                            </div>
                                        @elseif($order->status === 'preparing' && $role === 'admin')
                                            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="admin-action-bar">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="shipped">
                                                <input type="hidden" name="delivery_status" value="assigned">
                                                <select name="courier_user_id" class="field-control">
                                                    @foreach($couriers as $c)
                                                        <option value="{{ $c->id }}" @selected($order->delivery?->courier_user_id === $c->id)>{{ $c->name }}</option>
                                                    @endforeach
                                                </select>
                                                <button type="submit" class="tag-btn tag-btn--canopy">3. Assign Courier &amp; Ship &rarr;</button>
                                            </form>
                                        @elseif($order->status === 'shipped' && $delStatus !== 'in_transit')
                                            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="admin-action-bar">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="shipped">
                                                <input type="hidden" name="delivery_status" value="in_transit">
                                                <input type="text" name="proof_notes" value="{{ $order->delivery?->proof_notes }}" placeholder="Checkpoint note (e.g. Picked up at San Juan bar)" class="field-control">
                                                <button type="submit" class="tag-btn">4. Start Courier Tracking &rarr;</button>
                                                @if($role === 'admin')
                                                    <span class="admin-order-card__meta">Courier step (Admin override available)</span>
                                                @endif
                                            </form>
                                        @elseif($order->status === 'shipped' && $delStatus === 'in_transit')
                                            <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="admin-action-bar">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="delivered">
                                                <input type="hidden" name="delivery_status" value="delivered">
                                                <input type="text" name="proof_notes" value="{{ $order->delivery?->proof_notes }}" placeholder="Handoff note (e.g. Left at front desk)" class="field-control">
                                                <button type="submit" class="tag-btn tag-btn--canopy">5. Courier Confirms Delivery &rarr;</button>
                                                @if($role === 'admin')
                                                    <span class="admin-order-card__meta">Courier step (Admin override available)</span>
                                                @endif
                                            </form>
                                        @elseif($order->status === 'delivered')
                                            <div style="font-size:0.9rem;color:var(--canopy-deep);font-weight:600;">
                                                Delivered {{ $order->delivery?->delivered_at ? 'on '.$order->delivery->delivered_at->format('M d, Y · g:i A') : '' }}
                                                @if($order->delivery?->proof_notes)
                                                    <span style="font-weight:400;color:var(--taupe-muted);"> &mdash; Note: {{ $order->delivery->proof_notes }}</span>
                                                @endif
                                            </div>
                                        @else
                                            <div class="admin-order-card__meta">Awaiting Admin prep &amp; dispatch.</div>
                                        @endif
                                    @endif
                                </div>
                            </article>
                        @endforeach
                    </div>
                @endif
            </div>

        @elseif($activeTab === 'inventory' && $role === 'admin')
            <div class="operate-grid-2" style="margin-bottom:2rem;">
                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Create New Drink or Merch Product</h2>
                    <form method="POST" action="{{ route('admin.products.store') }}" enctype="multipart/form-data" class="form-stack">
                        @csrf
                        <div class="form-row-2">
                            <div class="field-group">
                                <label class="field-label">Product Name</label>
                                <input type="text" name="name" required placeholder="e.g. Siargao Calamansi Matcha" class="field-control">
                            </div>
                            <div class="field-group">
                                <label class="field-label">SKU</label>
                                <input type="text" name="sku" required placeholder="CI-SRG-099" class="field-control">
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="field-group">
                                <label class="field-label">Island Series</label>
                                <select name="island_series_id" class="field-control" required>
                                    @foreach($islands as $island)
                                        <option value="{{ $island->id }}">{{ $island->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Category</label>
                                <select name="category_id" class="field-control" required>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="field-group">
                                <label class="field-label">Product Nature</label>
                                <select name="is_perishable" class="field-control" required>
                                    <option value="1">Fresh Beverage (Perishable · No Restock)</option>
                                    <option value="0">Isla Merch (Non-Perishable · Auto-Restock)</option>
                                </select>
                            </div>
                            <div class="field-group">
                                <label class="field-label">Serve Temp</label>
                                <select name="serve_temp" class="field-control" required>
                                    <option value="iced">Iced</option>
                                    <option value="hot">Hot</option>
                                    <option value="na">Merch / One Size</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-row-2">
                            <div class="field-group">
                                <label class="field-label">Default Variant Label</label>
                                <input type="text" name="variant_label" value="Iced 16oz" required class="field-control">
                            </div>
                            <div class="field-group">
                                <label class="field-label">Price (₱) &amp; Opening Stock</label>
                                <div style="display:flex;gap:0.45rem;">
                                    <input type="number" name="base_price" step="0.01" min="1" value="195" required class="field-control tabular" placeholder="Price">
                                    <input type="number" name="stock_qty" min="0" value="25" required class="field-control tabular" placeholder="Qty">
                                </div>
                            </div>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Short Excerpt</label>
                            <input type="text" name="short_excerpt" required placeholder="One-line menu description" class="field-control">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Tasting Notes / Story</label>
                            <textarea name="description" rows="2" class="field-control" required placeholder="Full story and ingredients..."></textarea>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Product Image Upload (JPG/PNG/WEBP)</label>
                            <input type="file" name="image" accept="image/*" class="field-control">
                        </div>
                        <button type="submit" class="tag-btn tag-btn--canopy">Save Product &amp; Variant</button>
                    </form>
                </div>

                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Catalog Categories</h2>
                    <form method="POST" action="{{ route('admin.categories.store') }}" class="form-stack">
                        @csrf
                        <div class="field-group">
                            <label class="field-label">Category Name</label>
                            <input type="text" name="name" required placeholder="e.g. Cold-Brewed Teas" class="field-control">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Type</label>
                            <select name="product_type" class="field-control" required>
                                <option value="drink">Drink</option>
                                <option value="merch">Merch</option>
                            </select>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Description</label>
                            <textarea name="description" rows="2" class="field-control"></textarea>
                        </div>
                        <button type="submit" class="tag-btn">Create Category</button>
                    </form>

                    <div style="margin-top:1.75rem;padding-top:1.25rem;border-top:1px solid var(--hairline);">
                        <div class="admin-section-label">Manage &amp; Archive Categories</div>
                        <div style="display:grid;gap:0.6rem;">
                            @foreach($categories as $cat)
                                <form method="POST" action="{{ route('admin.categories.update', $cat) }}" style="display:grid;grid-template-columns:1fr 100px auto auto;gap:0.45rem;align-items:center;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="name" value="{{ $cat->name }}" class="field-control" style="padding:0.42rem 0.6rem;" required>
                                    <select name="product_type" class="field-control" style="padding:0.42rem 0.5rem;">
                                        <option value="drink" @selected($cat->product_type === 'drink')>Drink</option>
                                        <option value="merch" @selected($cat->product_type === 'merch')>Merch</option>
                                    </select>
                                    <label style="font-family:var(--font-mono);font-size:0.8rem;display:flex;gap:0.3rem;align-items:center;white-space:nowrap;">
                                        <input type="checkbox" name="is_archived" value="1" @checked($cat->is_archived)> Archived
                                    </label>
                                    <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Save</button>
                                </form>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <div class="ledger-panel">
                <div class="admin-filter-bar" style="border-bottom:none;padding-bottom:0;margin-bottom:1.25rem;">
                    <h2 class="ledger-panel__title" style="margin:0;border:0;padding:0;">
                        All Products, Variants, Stock &amp; Pricing ({{ $products->count() }})
                    </h2>
                    <input type="search" id="adminProductSearch" placeholder="Search product name or SKU" class="field-control" style="max-width:280px;padding:0.5rem 0.75rem;">
                </div>

                <div class="admin-card-list" id="adminProductList">
                    @foreach($products as $prod)
                        <article class="admin-product-card" data-product-search="{{ strtolower($prod->name.' '.$prod->sku.' '.$prod->islandSeries->name) }}">
                            <div class="admin-product-card__head">
                                <div class="admin-product-card__media">
                                    <img src="{{ $prod->imageUrl() }}" alt="{{ $prod->name }}" class="admin-product-card__thumb">
                                    <div>
                                        <strong style="font-size:1rem;">{{ $prod->name }}</strong>
                                        <div style="font-family:var(--font-mono);font-size:0.82rem;color:var(--taupe-muted);margin-top:0.15rem;">
                                            {{ $prod->sku }} &middot; {{ $prod->islandSeries->name }}
                                        </div>
                                        @if($prod->is_archived)
                                            <span class="status-tag status-tag--low" style="margin-top:0.35rem;display:inline-block;">Archived</span>
                                        @endif
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('admin.products.delete', $prod) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Delete / Archive</button>
                                </form>
                            </div>

                            <div class="admin-product-card__body">
                                <form method="POST" action="{{ route('admin.products.update', $prod) }}" enctype="multipart/form-data" class="form-stack" style="gap:0.6rem;">
                                    @csrf
                                    @method('PATCH')
                                    <div class="admin-section-label">Product Details</div>
                                    <input type="text" name="name" value="{{ $prod->name }}" class="field-control" required>
                                    <div class="form-row-2">
                                        <select name="island_series_id" class="field-control">
                                            @foreach($islands as $island)
                                                <option value="{{ $island->id }}" @selected($prod->island_series_id === $island->id)>{{ $island->name }}</option>
                                            @endforeach
                                        </select>
                                        <select name="category_id" class="field-control">
                                            @foreach($categories as $cat)
                                                <option value="{{ $cat->id }}" @selected($prod->category_id === $cat->id)>{{ $cat->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <input type="text" name="short_excerpt" value="{{ $prod->short_excerpt }}" class="field-control" required>
                                    <div style="display:flex;gap:0.75rem;align-items:center;flex-wrap:wrap;">
                                        <input type="number" name="base_price" step="0.01" value="{{ $prod->base_price }}" class="field-control tabular" style="width:120px;">
                                        <label style="font-family:var(--font-mono);font-size:0.82rem;display:flex;gap:0.35rem;align-items:center;">
                                            <input type="checkbox" name="is_archived" value="1" @checked($prod->is_archived)> Archived
                                        </label>
                                        <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Update Product</button>
                                    </div>
                                    <input type="file" name="image" accept="image/*" class="field-control">
                                </form>

                                <div>
                                    <div class="admin-section-label">Variants, Price &amp; Stock</div>
                                    @foreach($prod->variants as $variant)
                                        <form method="POST" action="{{ route('admin.variants.update', $variant) }}" class="admin-variant-row">
                                            @csrf
                                            @method('PATCH')
                                            <span style="font-family:var(--font-mono);font-size:0.82rem;">{{ $variant->label }}</span>
                                            <input type="number" name="price" step="0.01" value="{{ $variant->price }}" class="field-control tabular" style="padding:0.4rem 0.5rem;">
                                            <input type="number" name="stock_qty" value="{{ $variant->stock_qty }}" min="0" class="field-control tabular" style="padding:0.4rem 0.5rem;">
                                            <label style="font-family:var(--font-mono);font-size:0.78rem;display:flex;gap:0.25rem;align-items:center;">
                                                <input type="checkbox" name="is_active" value="1" @checked($variant->is_active)> Active
                                            </label>
                                            <button type="submit" class="tag-btn tag-btn--canopy tag-btn--sm">Save</button>
                                        </form>
                                    @endforeach

                                    <details style="margin-top:0.65rem;">
                                        <summary style="cursor:pointer;font-family:var(--font-mono);font-size:0.8rem;letter-spacing:0.06em;text-transform:uppercase;color:var(--canopy-deep);">+ Add Variant</summary>
                                        <form method="POST" action="{{ route('admin.variants.store', $prod) }}" style="display:grid;grid-template-columns:1fr 96px;gap:0.45rem;margin-top:0.5rem;padding:0.75rem;border:1px solid var(--hairline);background:var(--bg-parchment);">
                                            @csrf
                                            <input type="text" name="label" placeholder="e.g. Iced 16oz" class="field-control" style="padding:0.4rem 0.55rem;" required>
                                            <select name="serve_temp" class="field-control" style="padding:0.4rem 0.45rem;">
                                                <option value="iced">Iced</option>
                                                <option value="hot">Hot</option>
                                                <option value="na">N/A</option>
                                            </select>
                                            <input type="number" name="price" step="0.01" placeholder="Price" class="field-control tabular" style="padding:0.4rem 0.55rem;" required>
                                            <input type="number" name="stock_qty" min="0" placeholder="Stock" class="field-control tabular" style="padding:0.4rem 0.55rem;" required>
                                            <button type="submit" class="tag-btn tag-btn--sm" style="grid-column:span 2;">Add Variant</button>
                                        </form>
                                    </details>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </div>
            </div>

        @elseif($activeTab === 'returns' && $role === 'admin')
            <div class="ledger-panel">
                <h2 class="ledger-panel__title">Return, Cancellation &amp; Refund Processing</h2>
                <p style="font-size:0.92rem;color:var(--taupe-muted);margin:0 0 1.25rem 0;">
                    Approving a non-perishable <strong>Isla Merch</strong> return automatically increments variant stock. Approving a perishable <strong>beverage spill refund</strong> issues payment refund without restocking.
                </p>

                <div class="admin-card-list">
                    @forelse($returns as $ret)
                        @php
                            $isDrink = $ret->orderItem ? $ret->orderItem->is_perishable : $ret->order->items->every(fn ($i) => $i->is_perishable);
                        @endphp
                        <article class="admin-order-card">
                            <div class="admin-order-card__head">
                                <div class="admin-order-card__id-group">
                                    <span class="admin-order-card__number">#{{ $ret->id }} &middot; {{ strtoupper(str_replace('_', ' ', $ret->request_type)) }}</span>
                                    <span class="admin-order-card__meta">Order #{{ $ret->order->order_number }}</span>
                                </div>
                                <div class="admin-order-card__status-group">
                                    <span class="status-tag status-tag--{{ $ret->status }}">{{ strtoupper($ret->status) }}</span>
                                    <span class="admin-order-card__total tabular">₱{{ number_format((float) $ret->refund_amount, 2) }}</span>
                                </div>
                            </div>

                            <div class="admin-order-card__body">
                                <div>
                                    <div class="admin-section-label">Customer &amp; Reason</div>
                                    <div class="admin-order-card__customer-name">{{ $ret->user->name }}</div>
                                    <div class="admin-order-card__sub">{{ $ret->reason }}</div>
                                    @if($ret->details)
                                        <div class="admin-order-card__sub" style="margin-top:0.25rem;">{{ $ret->details }}</div>
                                    @endif
                                </div>
                                <div>
                                    <div class="admin-section-label">Item &amp; Restock Policy</div>
                                    <div style="font-size:0.92rem;margin-bottom:0.35rem;">
                                        @if($ret->orderItem)
                                            {{ $ret->orderItem->product_name_snapshot }} ({{ $ret->orderItem->variant_label_snapshot }})
                                        @else
                                            Full Order
                                        @endif
                                    </div>
                                    <div style="font-family:var(--font-mono);font-size:0.82rem;color:var(--taupe-muted);">
                                        {{ $isDrink ? 'Perishable: Refund only (no restock)' : 'Merch: Auto-restock on approval' }}
                                        @if($ret->restock_applied)
                                            &middot; <strong style="color:var(--canopy-deep);">Restock Applied</strong>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="admin-order-card__footer">
                                @if($ret->status === 'pending')
                                    <form method="POST" action="{{ route('admin.returns.process', $ret) }}" class="admin-action-bar">
                                        @csrf
                                        <input type="text" name="admin_remarks" placeholder="Resolution remarks" class="field-control">
                                        <button type="submit" name="decision" value="approved" class="tag-btn tag-btn--canopy">Approve &amp; Refund</button>
                                        <button type="submit" name="decision" value="rejected" class="tag-btn tag-btn--outline">Reject</button>
                                    </form>
                                @else
                                    <div style="font-size:0.88rem;color:var(--taupe-muted);">
                                        Resolution: {{ $ret->admin_remarks ?: 'Processed' }}
                                    </div>
                                @endif
                            </div>
                        </article>
                    @empty
                        <p style="font-size:0.95rem;color:var(--taupe-muted);margin:0;">No return or refund requests yet.</p>
                    @endforelse
                </div>
            </div>

        @elseif($activeTab === 'team' && $role === 'admin')
            <div class="operate-grid-even" style="margin-bottom:2rem;">
                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Employee Role Permissions (RBAC)</h2>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Operator</th>
                                    <th>Permission Level</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($teamMembers as $member)
                                    <tr>
                                        <td>
                                            <strong>{{ $member->name }}</strong>
                                            <div style="font-size:0.84rem;color:var(--taupe-muted);">{{ $member->email }}</div>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.users.role', $member) }}" style="display:flex;gap:0.45rem;flex-wrap:wrap;align-items:center;">
                                                @csrf
                                                @method('PATCH')
                                                <select name="role" class="field-control" style="width:140px;padding:0.4rem 0.5rem;">
                                                    <option value="admin" @selected($member->role === 'admin')>Admin (Full)</option>
                                                    <option value="courier" @selected($member->role === 'courier')>Courier (Fulfill)</option>
                                                </select>
                                                <select name="is_active" class="field-control" style="width:105px;padding:0.4rem 0.5rem;">
                                                    <option value="1" @selected($member->is_active)>Active</option>
                                                    <option value="0" @selected(! $member->is_active)>Inactive</option>
                                                </select>
                                                <button type="submit" class="tag-btn tag-btn--canopy tag-btn--sm">Update</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Add Admin or Courier Account</h2>
                    <form method="POST" action="{{ route('admin.team.store') }}" class="form-stack">
                        @csrf
                        <div class="form-row-2">
                            <input type="text" name="name" placeholder="Full Name" required class="field-control">
                            <input type="email" name="email" placeholder="operator@cafeisla.ph" required class="field-control">
                        </div>
                        <div class="form-row-2">
                            <select name="role" class="field-control" required>
                                <option value="courier">Fulfillment Courier</option>
                                <option value="admin">Admin</option>
                            </select>
                            @include('partials.password-input', ['name' => 'password', 'placeholder' => 'Min 8 chars', 'minlength' => 8])
                        </div>
                        <button type="submit" class="tag-btn">Create Operator Account</button>
                    </form>
                </div>
            </div>

            <div class="ledger-panel">
                <h2 class="ledger-panel__title">Customer Accounts &amp; Order History ({{ $customers->count() }})</h2>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Customer</th>
                                <th>Orders</th>
                                <th>Lifetime Spend</th>
                                <th>Account Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($customers as $cust)
                                <tr>
                                    <td>
                                        <strong style="font-size:0.98rem;">{{ $cust->name }}</strong>
                                        <div style="font-size:0.85rem;color:var(--taupe-muted);">
                                            {{ $cust->email }} &middot; {{ $cust->phone ?? 'No phone' }}
                                        </div>
                                        <details style="margin-top:0.6rem;">
                                            <summary style="cursor:pointer;font-family:var(--font-mono);font-size:0.8rem;letter-spacing:0.04em;text-transform:uppercase;color:var(--canopy-deep);">
                                                Inspect Orders &amp; Support ({{ $cust->orders_count }} orders &middot; {{ $cust->returnRequests->count() }} requests)
                                            </summary>
                                            <div style="margin-top:0.6rem;padding:0.9rem;border:1px solid var(--hairline);background:var(--bg-parchment);display:grid;gap:0.85rem;font-size:0.88rem;">
                                                <div>
                                                    <div class="admin-section-label">Order History</div>
                                                    @forelse($cust->orders as $cOrder)
                                                        <div style="display:flex;justify-content:space-between;gap:0.75rem;padding:0.3rem 0;border-bottom:1px solid var(--hairline);">
                                                            <span class="tabular" style="font-family:var(--font-mono);font-size:0.82rem;">
                                                                #{{ $cOrder->order_number }} &middot; {{ optional($cOrder->placed_at)->format('M d, Y') ?? 'Pending' }} &middot; {{ $cOrder->status }}
                                                            </span>
                                                            <span class="tabular" style="font-family:var(--font-mono);font-size:0.82rem;">₱{{ number_format((float) $cOrder->grand_total, 2) }}</span>
                                                        </div>
                                                    @empty
                                                        <div style="color:var(--taupe-muted);">No orders placed yet.</div>
                                                    @endforelse
                                                </div>
                                                <div>
                                                    <div class="admin-section-label">Support &amp; Return Requests</div>
                                                    @forelse($cust->returnRequests as $cReq)
                                                        <div style="padding:0.3rem 0;border-bottom:1px solid var(--hairline);font-size:0.84rem;">
                                                            <span style="font-family:var(--font-mono);text-transform:uppercase;">{{ $cReq->request_type }}</span>
                                                            ({{ $cReq->status }}) on #{{ $cReq->order->order_number ?? 'N/A' }} &mdash; {{ $cReq->reason }}
                                                        </div>
                                                    @empty
                                                        <div style="color:var(--taupe-muted);">No support or return tickets.</div>
                                                    @endforelse
                                                </div>
                                                <div>
                                                    <div class="admin-section-label">Saved Addresses</div>
                                                    @forelse($cust->addresses as $cAddr)
                                                        <div style="font-size:0.84rem;color:var(--espresso-ink);">
                                                            <strong>{{ $cAddr->label }}</strong> ({{ $cAddr->delivery_zone }}): {{ $cAddr->formattedAddress() }}
                                                        </div>
                                                    @empty
                                                        <div style="color:var(--taupe-muted);">No saved addresses.</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        </details>
                                    </td>
                                    <td class="tabular" style="font-family:var(--font-mono);">{{ $cust->orders_count }}</td>
                                    <td class="tabular" style="font-family:var(--font-mono);">₱{{ number_format((float) ($cust->orders_sum_grand_total ?? 0), 2) }}</td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.users.role', $cust) }}" style="display:flex;gap:0.45rem;align-items:center;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="role" value="{{ $cust->role }}">
                                            <select name="is_active" class="field-control" style="width:120px;padding:0.4rem 0.5rem;">
                                                <option value="1" @selected($cust->is_active)>Active</option>
                                                <option value="0" @selected(! $cust->is_active)>Suspended</option>
                                            </select>
                                            <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Save</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
            </section>
        </div>
    </div>

    <script>
        (function () {
            var activeStatus = 'all';
            var orderCards = document.querySelectorAll('#adminOrderList .admin-order-card');
            var orderPills = document.querySelectorAll('[data-order-filter]');
            var orderSearch = document.getElementById('adminOrderSearch');

            function filterOrders() {
                var query = (orderSearch ? orderSearch.value : '').trim().toLowerCase();
                orderCards.forEach(function (card) {
                    var statusMatch = activeStatus === 'all' || card.dataset.orderStatus === activeStatus;
                    var textMatch = !query || (card.dataset.orderSearch || '').indexOf(query) !== -1;
                    card.style.display = (statusMatch && textMatch) ? '' : 'none';
                });
            }

            orderPills.forEach(function (pill) {
                pill.addEventListener('click', function () {
                    orderPills.forEach(function (p) { p.classList.remove('is-active'); });
                    pill.classList.add('is-active');
                    activeStatus = pill.dataset.orderFilter;
                    filterOrders();
                });
            });

            if (orderSearch) {
                orderSearch.addEventListener('input', filterOrders);
            }

            var prodSearch = document.getElementById('adminProductSearch');
            var prodCards = document.querySelectorAll('#adminProductList .admin-product-card');
            if (prodSearch) {
                prodSearch.addEventListener('input', function () {
                    var q = prodSearch.value.trim().toLowerCase();
                    prodCards.forEach(function (card) {
                        card.style.display = (!q || (card.dataset.productSearch || '').indexOf(q) !== -1) ? '' : 'none';
                    });
                });
            }
        })();
    </script>
@endsection
