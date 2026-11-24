@extends('layouts.app')

@section('title', 'Operator Portal · Café Isla')

@section('content')
    <section class="admin-shell-bar">
        <div class="container">
            <div class="admin-shell-bar__top">
                <div>
                    <h1 class="admin-shell-bar__title">Café Isla Operations Console</h1>
                    <div style="font-family:var(--font-mono);font-size:0.82rem;opacity:0.82;margin-top:0.25rem;">
                        Signed in as {{ $user->name }} ({{ $user->email }})
                    </div>
                </div>
                <div style="display:flex;gap:0.65rem;flex-wrap:wrap;align-items:center;">
                    <span class="role-pill">Role: {{ strtoupper($role) }}</span>
                    <a href="{{ route('diagrams.index') }}" class="tag-btn tag-btn--outline tag-btn--sm" style="border-color:#F6F1E6;color:#F6F1E6;">Diagrams</a>
                    <form method="POST" action="{{ route('auth.logout') }}">
                        @csrf
                        <button type="submit" class="tag-btn tag-btn--sm">Sign Out</button>
                    </form>
                </div>
            </div>

            <nav class="admin-tabs" aria-label="Portal Workspace Tabs">
                @if($role === 'admin')
                    <a href="{{ route('admin.index', ['tab' => 'analytics']) }}" class="admin-tab {{ $activeTab === 'analytics' ? 'is-active' : '' }}">Reporting &amp; Analytics</a>
                @endif
                <a href="{{ route('admin.index', ['tab' => 'orders']) }}" class="admin-tab {{ $activeTab === 'orders' ? 'is-active' : '' }}">Orders &amp; Fulfillment ({{ $orders->count() }})</a>
                @if($role === 'admin')
                    <a href="{{ route('admin.index', ['tab' => 'inventory']) }}" class="admin-tab {{ $activeTab === 'inventory' ? 'is-active' : '' }}">Inventory &amp; Uploads ({{ $products->count() }})</a>
                    <a href="{{ route('admin.index', ['tab' => 'returns']) }}" class="admin-tab {{ $activeTab === 'returns' ? 'is-active' : '' }}">Returns &amp; Refunds ({{ $returns->count() }})</a>
                @endif
                @if($role === 'admin')
                    <a href="{{ route('admin.index', ['tab' => 'team']) }}" class="admin-tab {{ $activeTab === 'team' ? 'is-active' : '' }}">Customers &amp; RBAC</a>
                @endif
            </nav>
        </div>
    </section>

    <section class="container operate-main">
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

                    <div style="margin-top:1rem;font-family:var(--font-mono);font-size:0.8rem;color:var(--taupe-muted);">
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
                                            <div style="font-family:var(--font-mono);font-size:0.74rem;color:var(--taupe-muted);">
                                                {{ $lv->label }} &middot; {{ $lv->product->islandSeries->name }}
                                            </div>
                                        </td>
                                        <td>
                                            <span class="status-tag status-tag--low tabular">{{ $lv->stock_qty }} left</span>
                                        </td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.variants.update', $lv) }}" style="display:flex;gap:0.35rem;">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="price" value="{{ $lv->price }}">
                                                <input type="number" name="stock_qty" value="{{ $lv->stock_qty + 20 }}" min="0" class="field-control tabular" style="width:70px;padding:0.3rem;">
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
            <div class="ledger-panel">
                <h2 class="ledger-panel__title">
                    {{ $role === 'courier' ? 'Courier Dispatch & Delivery Updates' : 'Incoming Orders & Fulfillment Queue' }}
                </h2>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer &amp; Shipping</th>
                                <th>Items &amp; Total</th>
                                <th>Status &amp; Payment</th>
                                <th>Update &amp; Assign Courier</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($orders as $order)
                                <tr>
                                    <td class="tabular" style="font-family:var(--font-mono);">
                                        <strong>#{{ $order->order_number }}</strong>
                                        <div style="font-size:0.72rem;color:var(--taupe-muted);">{{ $order->placed_at->format('M d, g:i A') }}</div>
                                        <div style="font-size:0.72rem;color:var(--taupe-muted);">{{ $order->delivery?->tracking_number }}</div>
                                    </td>
                                    <td>
                                        <strong>{{ $order->shipping_recipient }}</strong>
                                        <div style="font-family:var(--font-mono);font-size:0.76rem;color:var(--taupe-muted);">
                                            {{ $order->shipping_phone }}<br>
                                            {{ $order->shipping_address_line }}, {{ $order->shipping_city }}, {{ $order->shipping_province }}
                                        </div>
                                    </td>
                                    <td>
                                        <ul style="margin:0;padding-left:1rem;font-size:0.82rem;">
                                            @foreach($order->items as $item)
                                                <li>
                                                    {{ $item->quantity }}&times; {{ $item->product_name_snapshot }} ({{ $item->variant_label_snapshot }})
                                                    @if($role !== 'courier' && $item->custom_notes)
                                                        <br><strong>Prep note</strong> {{ $item->custom_notes }}
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                        <div class="tabular" style="font-family:var(--font-mono);font-weight:700;margin-top:0.35rem;">
                                            ₱{{ number_format((float) $order->grand_total, 2) }}
                                        </div>
                                    </td>
                                    <td>
                                        <span class="status-tag status-tag--{{ $order->status }}">{{ strtoupper($order->status) }}</span>
                                        <div style="font-family:var(--font-mono);font-size:0.74rem;margin-top:0.35rem;">
                                            Pay: {{ $order->payment?->methodLabel() }} ({{ strtoupper($order->payment?->status ?? 'pending') }})<br>
                                            Delivery: {{ strtoupper($order->delivery?->status ?? 'unassigned') }}
                                            @if($order->delivery?->courier)
                                                <br>Rider: {{ $order->delivery->courier->name }}
                                            @endif
                                        </div>
                                        @if($order->delivery?->tracking_number)
                                            <div style="margin-top:0.35rem;">
                                                <a href="{{ route('orders.track', ['code' => $order->order_number]) }}" style="font-family:var(--font-mono);font-size:0.72rem;color:var(--canopy);text-decoration:underline;text-underline-offset:3px;">
                                                    Track {{ $order->delivery->tracking_number }}
                                                </a>
                                            </div>
                                        @endif
                                    </td>
                                    <td style="min-width:340px;">
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
                                                1 => 'Confirm',
                                                2 => 'Prep',
                                                3 => 'Shipped',
                                                4 => 'Tracking',
                                                5 => 'Confirm Deliv',
                                                6 => 'Delivered',
                                            ];
                                        @endphp

                                        @if($order->status === 'cancelled')
                                            <div style="font-family:var(--font-mono);font-size:0.76rem;color:var(--clay);padding:0.45rem 0.6rem;border:1px solid var(--clay);background:rgba(196,85,58,0.06);">
                                                Order Cancelled &mdash; No further fulfillment steps.
                                            </div>
                                        @else
                                            <div style="display:grid;grid-template-columns:repeat(6, 1fr);gap:2px;margin-bottom:0.55rem;" aria-label="Fulfillment Pipeline">
                                                @foreach($pipelineSteps as $stepNum => $stepLabel)
                                                    @php
                                                        $isDone = $stageIndex >= $stepNum;
                                                        $isCurrentNext = ($stageIndex + 1) === $stepNum;
                                                    @endphp
                                                    <div style="text-align:center;padding:0.25rem 0.15rem;font-family:var(--font-mono);font-size:0.65rem;letter-spacing:0.02em;border-top:2px solid {{ $isDone ? 'var(--canopy)' : ($isCurrentNext ? 'var(--clay)' : 'var(--border-warm)') }};background:{{ $isDone ? 'rgba(28,49,39,0.08)' : 'transparent' }};color:{{ $isDone ? 'var(--canopy)' : ($isCurrentNext ? 'var(--clay)' : 'var(--taupe-muted)') }};font-weight:{{ ($isDone || $isCurrentNext) ? '700' : '400' }};">
                                                        {{ $stepLabel }}
                                                    </div>
                                                @endforeach
                                            </div>

                                            @if($order->status === 'pending' && $role === 'admin')
                                                <div style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center;">
                                                    <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="confirmed">
                                                        <button type="submit" class="tag-btn tag-btn--canopy tag-btn--sm">1. Confirm Order &rarr;</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Cancel</button>
                                                    </form>
                                                </div>
                                            @elseif($order->status === 'confirmed' && $role === 'admin')
                                                <div style="display:flex;gap:0.4rem;flex-wrap:wrap;align-items:center;">
                                                    <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="preparing">
                                                        <button type="submit" class="tag-btn tag-btn--canopy tag-btn--sm">2. Start Preparing &rarr;</button>
                                                    </form>
                                                    <form method="POST" action="{{ route('admin.orders.update', $order) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="cancelled">
                                                        <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Cancel</button>
                                                    </form>
                                                </div>
                                            @elseif($order->status === 'preparing' && $role === 'admin')
                                                <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="form-stack" style="gap:0.35rem;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="shipped">
                                                    <input type="hidden" name="delivery_status" value="assigned">
                                                    <div style="display:flex;gap:0.35rem;">
                                                        <select name="courier_user_id" class="field-control" style="padding:0.3rem 0.45rem;">
                                                            @foreach($couriers as $c)
                                                                <option value="{{ $c->id }}" @selected($order->delivery?->courier_user_id === $c->id)>{{ $c->name }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button type="submit" class="tag-btn tag-btn--canopy tag-btn--sm" style="white-space:nowrap;">3. Assign Courier &amp; Ship &rarr;</button>
                                                    </div>
                                                </form>
                                            @elseif($order->status === 'shipped' && $delStatus !== 'in_transit')
                                                <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="form-stack" style="gap:0.35rem;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="shipped">
                                                    <input type="hidden" name="delivery_status" value="in_transit">
                                                    <div style="display:flex;gap:0.35rem;">
                                                        <input type="text" name="proof_notes" value="{{ $order->delivery?->proof_notes }}" placeholder="Rider checkpoint note" class="field-control" style="padding:0.3rem 0.45rem;">
                                                        <button type="submit" class="tag-btn tag-btn--sm" style="white-space:nowrap;">4. Start Courier Tracking &rarr;</button>
                                                    </div>
                                                    @if($role === 'admin')
                                                        <div style="font-family:var(--font-mono);font-size:0.68rem;color:var(--taupe-muted);">Courier step (Admin override available)</div>
                                                    @endif
                                                </form>
                                            @elseif($order->status === 'shipped' && $delStatus === 'in_transit')
                                                <form method="POST" action="{{ route('admin.orders.update', $order) }}" class="form-stack" style="gap:0.35rem;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="delivered">
                                                    <input type="hidden" name="delivery_status" value="delivered">
                                                    <div style="display:flex;gap:0.35rem;">
                                                        <input type="text" name="proof_notes" value="{{ $order->delivery?->proof_notes }}" placeholder="Recipient / handoff proof" class="field-control" style="padding:0.3rem 0.45rem;">
                                                        <button type="submit" class="tag-btn tag-btn--canopy tag-btn--sm" style="white-space:nowrap;">5. Courier Confirms Delivery &rarr;</button>
                                                    </div>
                                                    @if($role === 'admin')
                                                        <div style="font-family:var(--font-mono);font-size:0.68rem;color:var(--taupe-muted);">Courier step (Admin override available)</div>
                                                    @endif
                                                </form>
                                            @elseif($order->status === 'delivered')
                                                <div style="font-family:var(--font-mono);font-size:0.75rem;color:var(--canopy);padding:0.4rem 0.55rem;border:1px solid var(--canopy);background:rgba(28,49,39,0.06);">
                                                    Delivered {{ $order->delivery?->delivered_at ? 'on '.$order->delivery->delivered_at->format('M d, g:i A') : '' }}
                                                    @if($order->delivery?->proof_notes)
                                                        <div style="color:var(--taupe-muted);margin-top:0.2rem;">Note: {{ $order->delivery->proof_notes }}</div>
                                                    @endif
                                                </div>
                                            @else
                                                <div style="font-family:var(--font-mono);font-size:0.74rem;color:var(--taupe-muted);">
                                                    Awaiting Admin prep &amp; dispatch.
                                                </div>
                                            @endif
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @elseif($activeTab === 'inventory' && $role === 'admin')
            <div class="operate-grid-2" style="margin-bottom:2rem;">
                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Create New Drink or Merch Product (with Image Upload)</h2>
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
                            <input type="text" name="short_excerpt" required placeholder="One-line tasting or fabric summary" class="field-control">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Full Description</label>
                            <textarea name="description" rows="2" required class="field-control" placeholder="Ingredients, origin, or sizing details"></textarea>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Upload Product Image (JPG/PNG/WEBP)</label>
                            <input type="file" name="image" accept="image/*" class="field-control">
                        </div>
                        <button type="submit" class="tag-btn tag-btn--canopy">Save Product &amp; Upload Image</button>
                    </form>
                </div>

                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Add Catalog Category</h2>
                    <form method="POST" action="{{ route('admin.categories.store') }}" class="form-stack">
                        @csrf
                        <div class="field-group">
                            <label class="field-label">Category Name</label>
                            <input type="text" name="name" required placeholder="e.g. Seasonal Island Pastries" class="field-control">
                        </div>
                        <div class="field-group">
                            <label class="field-label">Type</label>
                            <select name="product_type" class="field-control">
                                <option value="drink">Drink / Café Item</option>
                                <option value="merch">Isla Merch / Lifestyle</option>
                            </select>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Description</label>
                            <textarea name="description" rows="2" class="field-control"></textarea>
                        </div>
                        <button type="submit" class="tag-btn">Create Category</button>
                    </form>

                    <div style="margin-top:1.5rem;padding-top:1.25rem;border-top:1px solid var(--border-warm);">
                        <div style="font-family:var(--font-mono);font-size:0.72rem;letter-spacing:0.12em;text-transform:uppercase;color:var(--taupe-muted);margin-bottom:0.75rem;">
                            Manage &amp; Archive Categories
                        </div>
                        <div style="display:grid;gap:0.55rem;">
                            @foreach($categories as $cat)
                                <form method="POST" action="{{ route('admin.categories.update', $cat) }}" style="display:grid;grid-template-columns:1fr 96px auto auto;gap:0.4rem;align-items:center;">
                                    @csrf
                                    @method('PATCH')
                                    <input type="text" name="name" value="{{ $cat->name }}" class="field-control" style="padding:0.35rem 0.5rem;" required>
                                    <select name="product_type" class="field-control" style="padding:0.35rem 0.4rem;">
                                        <option value="drink" @selected($cat->product_type === 'drink')>Drink</option>
                                        <option value="merch" @selected($cat->product_type === 'merch')>Merch</option>
                                    </select>
                                    <label style="font-family:var(--font-mono);font-size:0.72rem;display:flex;gap:0.25rem;align-items:center;white-space:nowrap;">
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
                <h2 class="ledger-panel__title">All Products, Variants, Stock &amp; Pricing ({{ $products->count() }})</h2>
                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Edit Details / Archive / Image</th>
                                <th>Variants &amp; Stock</th>
                                <th>Remove</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($products as $prod)
                                <tr>
                                    <td style="min-width:190px;">
                                        <div style="display:flex;gap:0.7rem;align-items:center;">
                                            <img src="{{ $prod->imageUrl() }}" alt="{{ $prod->name }}" style="width:52px;height:62px;object-fit:cover;border:1px solid var(--espresso-ink);">
                                            <div>
                                                <strong>{{ $prod->name }}</strong>
                                                <div style="font-family:var(--font-mono);font-size:0.74rem;color:var(--taupe-muted);">
                                                    {{ $prod->sku }}<br>{{ $prod->islandSeries->name }}
                                                </div>
                                                @if($prod->is_archived)
                                                    <span class="status-tag status-tag--low">Archived</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td style="min-width:280px;">
                                        <form method="POST" action="{{ route('admin.products.update', $prod) }}" enctype="multipart/form-data" class="form-stack" style="gap:0.4rem;">
                                            @csrf
                                            @method('PATCH')
                                            <input type="text" name="name" value="{{ $prod->name }}" class="field-control" required>
                                            <div style="display:flex;gap:0.35rem;">
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
                                            <div style="display:flex;gap:0.45rem;align-items:center;">
                                                <input type="number" name="base_price" step="0.01" value="{{ $prod->base_price }}" class="field-control tabular" style="width:110px;">
                                                <label style="font-family:var(--font-mono);font-size:0.74rem;display:flex;gap:0.3rem;align-items:center;">
                                                    <input type="checkbox" name="is_archived" value="1" @checked($prod->is_archived)> Archived
                                                </label>
                                            </div>
                                            <input type="file" name="image" accept="image/*" class="field-control">
                                            <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Update Product</button>
                                        </form>
                                    </td>
                                    <td style="min-width:290px;">
                                        @foreach($prod->variants as $variant)
                                            <form method="POST" action="{{ route('admin.variants.update', $variant) }}" style="display:flex;gap:0.35rem;align-items:center;margin-bottom:0.4rem;flex-wrap:wrap;">
                                                @csrf
                                                @method('PATCH')
                                                <span style="font-family:var(--font-mono);font-size:0.74rem;min-width:95px;">{{ $variant->label }}</span>
                                                <input type="number" name="price" step="0.01" value="{{ $variant->price }}" class="field-control tabular" style="width:74px;padding:0.3rem;">
                                                <input type="number" name="stock_qty" value="{{ $variant->stock_qty }}" min="0" class="field-control tabular" style="width:58px;padding:0.3rem;">
                                                <label style="font-family:var(--font-mono);font-size:0.7rem;display:flex;gap:0.25rem;align-items:center;">
                                                    <input type="checkbox" name="is_active" value="1" @checked($variant->is_active)> Active
                                                </label>
                                                <button type="submit" class="tag-btn tag-btn--canopy tag-btn--sm">Save</button>
                                            </form>
                                        @endforeach
                                        <details style="margin-top:0.5rem;">
                                            <summary style="cursor:pointer;font-family:var(--font-mono);font-size:0.72rem;letter-spacing:0.08em;text-transform:uppercase;color:var(--canopy);">+ Add Variant</summary>
                                            <form method="POST" action="{{ route('admin.variants.store', $prod) }}" style="display:grid;grid-template-columns:1fr 84px;gap:0.35rem;margin-top:0.45rem;padding:0.55rem;border:1px solid var(--border-warm);background:var(--cream);">
                                                @csrf
                                                <input type="text" name="label" placeholder="e.g. Iced 16oz" class="field-control" style="padding:0.3rem 0.45rem;" required>
                                                <select name="serve_temp" class="field-control" style="padding:0.3rem 0.35rem;">
                                                    <option value="iced">Iced</option>
                                                    <option value="hot">Hot</option>
                                                    <option value="na">N/A</option>
                                                </select>
                                                <input type="number" name="price" step="0.01" placeholder="Price" class="field-control tabular" style="padding:0.3rem 0.45rem;" required>
                                                <input type="number" name="stock_qty" min="0" placeholder="Stock" class="field-control tabular" style="padding:0.3rem 0.45rem;" required>
                                                <button type="submit" class="tag-btn tag-btn--sm" style="grid-column:span 2;">Add Variant</button>
                                            </form>
                                        </details>
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.products.delete', $prod) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Delete / Archive</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @elseif($activeTab === 'returns' && $role === 'admin')
            <div class="ledger-panel">
                <h2 class="ledger-panel__title">Return, Cancellation &amp; Refund Processing</h2>
                <p style="font-family:var(--font-mono);font-size:0.82rem;color:var(--taupe-muted);margin-top:0;">
                    Approving a non-perishable <strong>Isla Merch</strong> return automatically increments variant stock. Approving a perishable <strong>beverage spill refund</strong> issues payment refund without restocking.
                </p>

                <div class="data-table-wrap">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Request</th>
                                <th>Order &amp; Item</th>
                                <th>Customer Reason</th>
                                <th>Policy &amp; Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($returns as $ret)
                                <tr>
                                    <td class="tabular" style="font-family:var(--font-mono);">
                                        #{{ $ret->id }}<br>
                                        <strong>{{ strtoupper(str_replace('_', ' ', $ret->request_type)) }}</strong>
                                    </td>
                                    <td>
                                        <strong>#{{ $ret->order->order_number }}</strong>
                                        <div style="font-family:var(--font-mono);font-size:0.76rem;color:var(--taupe-muted);">
                                            @if($ret->orderItem)
                                                {{ $ret->orderItem->product_name_snapshot }} ({{ $ret->orderItem->variant_label_snapshot }})
                                            @else
                                                Full Order
                                            @endif
                                            <br>Refund: ₱{{ number_format((float) $ret->refund_amount, 2) }}
                                        </div>
                                    </td>
                                    <td>
                                        <strong>{{ $ret->user->name }}</strong>
                                        <div style="font-size:0.84rem;">{{ $ret->reason }}</div>
                                        @if($ret->details)
                                            <div style="font-family:var(--font-mono);font-size:0.74rem;color:var(--taupe-muted);">{{ $ret->details }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="status-tag status-tag--{{ $ret->status }}">{{ strtoupper($ret->status) }}</span>
                                        <div style="font-family:var(--font-mono);font-size:0.74rem;margin-top:0.35rem;">
                                            @php
                                                $isDrink = $ret->orderItem ? $ret->orderItem->is_perishable : $ret->order->items->every(fn ($i) => $i->is_perishable);
                                            @endphp
                                            {{ $isDrink ? 'Perishable: Refund only' : 'Merch: Auto-restock on approval' }}
                                            @if($ret->restock_applied)
                                                <br><strong>Restock Applied</strong>
                                            @endif
                                        </div>
                                    </td>
                                    <td style="min-width:220px;">
                                        @if($ret->status === 'pending')
                                            <form method="POST" action="{{ route('admin.returns.process', $ret) }}" class="form-stack" style="gap:0.4rem;">
                                                @csrf
                                                <input type="text" name="admin_remarks" placeholder="Resolution remarks" class="field-control">
                                                <div style="display:flex;gap:0.4rem;">
                                                    <button type="submit" name="decision" value="approved" class="tag-btn tag-btn--canopy tag-btn--sm">Approve &amp; Refund</button>
                                                    <button type="submit" name="decision" value="rejected" class="tag-btn tag-btn--outline tag-btn--sm">Reject</button>
                                                </div>
                                            </form>
                                        @else
                                            <div style="font-family:var(--font-mono);font-size:0.76rem;color:var(--taupe-muted);">
                                                {{ $ret->admin_remarks }}
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

        @elseif($activeTab === 'team' && $role === 'admin')
            <div class="operate-grid-2">
                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Customer Accounts &amp; Order History</h2>
                    <div class="data-table-wrap">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Customer</th>
                                    <th>Orders</th>
                                    <th>Lifetime Spend</th>
                                    <th>Access</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($customers as $cust)
                                    <tr>
                                        <td>
                                            <strong>{{ $cust->name }}</strong>
                                            <div style="font-family:var(--font-mono);font-size:0.75rem;color:var(--taupe-muted);">
                                                {{ $cust->email }} &middot; {{ $cust->phone ?? 'No phone' }}
                                            </div>
                                            <details style="margin-top:0.55rem;">
                                                <summary style="cursor:pointer;font-family:var(--font-mono);font-size:0.72rem;letter-spacing:0.06em;text-transform:uppercase;color:var(--canopy);">
                                                    Inspect Orders &amp; Support ({{ $cust->orders_count }} orders &middot; {{ $cust->returnRequests->count() }} requests)
                                                </summary>
                                                <div style="margin-top:0.5rem;padding:0.75rem;border:1px solid var(--border-warm);background:var(--cream);display:grid;gap:0.75rem;font-size:0.82rem;">
                                                    <div>
                                                        <div style="font-family:var(--font-mono);font-size:0.7rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--taupe-muted);margin-bottom:0.3rem;">Order History</div>
                                                        @forelse($cust->orders as $cOrder)
                                                            <div style="display:flex;justify-content:space-between;gap:0.5rem;padding:0.25rem 0;border-bottom:1px solid var(--border-warm);">
                                                                <span class="tabular" style="font-family:var(--font-mono);font-size:0.74rem;">
                                                                    #{{ $cOrder->order_number }} &middot; {{ optional($cOrder->placed_at)->format('M d, Y') ?? 'Pending' }} &middot; {{ $cOrder->status }}
                                                                </span>
                                                                <span class="tabular" style="font-family:var(--font-mono);font-size:0.74rem;">₱{{ number_format((float) $cOrder->grand_total, 2) }}</span>
                                                            </div>
                                                        @empty
                                                            <div style="color:var(--taupe-muted);font-size:0.78rem;">No orders placed yet.</div>
                                                        @endforelse
                                                    </div>
                                                    <div>
                                                        <div style="font-family:var(--font-mono);font-size:0.7rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--taupe-muted);margin-bottom:0.3rem;">Support &amp; Return Requests</div>
                                                        @forelse($cust->returnRequests as $cReq)
                                                            <div style="padding:0.25rem 0;border-bottom:1px solid var(--border-warm);font-size:0.76rem;">
                                                                <span style="font-family:var(--font-mono);text-transform:uppercase;">{{ $cReq->request_type }}</span>
                                                                ({{ $cReq->status }}) on #{{ $cReq->order->order_number ?? 'N/A' }} &mdash; {{ $cReq->reason }}
                                                            </div>
                                                        @empty
                                                            <div style="color:var(--taupe-muted);font-size:0.78rem;">No support or return tickets.</div>
                                                        @endforelse
                                                    </div>
                                                    <div>
                                                        <div style="font-family:var(--font-mono);font-size:0.7rem;text-transform:uppercase;letter-spacing:0.08em;color:var(--taupe-muted);margin-bottom:0.3rem;">Saved Addresses</div>
                                                        @forelse($cust->addresses as $cAddr)
                                                            <div style="font-size:0.76rem;color:var(--espresso-ink);">
                                                                <strong>{{ $cAddr->label }}</strong> ({{ $cAddr->delivery_zone }}): {{ $cAddr->formattedAddress() }}
                                                            </div>
                                                        @empty
                                                            <div style="color:var(--taupe-muted);font-size:0.78rem;">No saved addresses.</div>
                                                        @endforelse
                                                    </div>
                                                </div>
                                            </details>
                                        </td>
                                        <td class="tabular" style="font-family:var(--font-mono);">{{ $cust->orders_count }}</td>
                                        <td class="tabular" style="font-family:var(--font-mono);">₱{{ number_format((float) ($cust->orders_sum_grand_total ?? 0), 2) }}</td>
                                        <td>
                                            <form method="POST" action="{{ route('admin.users.role', $cust) }}" style="display:flex;gap:0.35rem;align-items:center;">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="role" value="{{ $cust->role }}">
                                                <select name="is_active" class="field-control" style="padding:0.3rem;">
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

                <div class="form-stack">
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
                                                <div style="font-family:var(--font-mono);font-size:0.74rem;color:var(--taupe-muted);">{{ $member->email }}</div>
                                            </td>
                                            <td>
                                                <form method="POST" action="{{ route('admin.users.role', $member) }}" style="display:flex;gap:0.35rem;flex-wrap:wrap;">
                                                    @csrf
                                                    @method('PATCH')
                                                    <select name="role" class="field-control" style="width:130px;padding:0.3rem;">
                                                        <option value="admin" @selected($member->role === 'admin')>Admin (Full)</option>
                                                        <option value="courier" @selected($member->role === 'courier')>Courier (Fulfill)</option>
                                                    </select>
                                                    <select name="is_active" class="field-control" style="width:95px;padding:0.3rem;">
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
            </div>
        @endif
    </section>
@endsection
