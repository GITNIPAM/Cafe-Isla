@extends('layouts.app')

@section('title', 'Customer Dashboard · Café Isla')

@section('content')
    <section class="admin-shell-bar">
        <div class="container">
            <div class="admin-shell-bar__top">
                <div>
                    <h1 class="admin-shell-bar__title">Welcome back, {{ $user->name }}</h1>
                    <div style="font-family:var(--font-mono);font-size:0.82rem;opacity:0.8;margin-top:0.25rem;">
                        {{ $user->email }} &middot; Role: {{ strtoupper($user->role) }}
                    </div>
                </div>
                <div style="display:flex;gap:0.65rem;flex-wrap:wrap;align-items:center;">
                    <a href="{{ route('menu.index') }}" class="tag-btn tag-btn--sm">Order Drinks</a>
                    @if($user->isOperator())
                        <a href="{{ route('admin.index') }}" class="tag-btn tag-btn--outline tag-btn--sm" style="border-color:#F6F1E6;color:#F6F1E6;">Open Operator Portal</a>
                    @endif
                    <form method="POST" action="{{ route('auth.logout') }}">
                        @csrf
                        <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm" style="border-color:#F6F1E6;color:#F6F1E6;">Sign Out</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <section class="container operate-main">
        <div class="operate-grid-2">
            <div class="form-stack">
                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Order History &amp; Returns ({{ $orders->count() }})</h2>
                    @if($orders->isEmpty())
                        <p style="font-family:var(--font-mono);font-size:0.84rem;color:var(--taupe-muted);">
                            You have not placed an order yet.
                        </p>
                    @else
                        <div class="form-stack">
                            @foreach($orders as $order)
                                <article style="border:1px solid var(--espresso-ink);padding:1.1rem;background:var(--bg-parchment);">
                                    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;align-items:center;">
                                        <div>
                                            <a class="tabular" href="{{ route('orders.track', ['order' => $order->order_number]) }}" style="font-family:var(--font-mono);font-size:0.95rem;">#{{ $order->order_number }}</a>
                                            <span style="font-family:var(--font-mono);font-size:0.78rem;color:var(--taupe-muted);margin-left:0.5rem;">
                                                {{ $order->placed_at->format('M d, Y g:i A') }}
                                            </span>
                                        </div>
                                        <div style="display:flex;gap:0.45rem;align-items:center;">
                                            <span class="status-tag status-tag--{{ $order->status }}">{{ strtoupper($order->status) }}</span>
                                            <span class="status-tag">{{ $order->payment?->methodLabel() }} ({{ strtoupper($order->payment?->status ?? 'pending') }})</span>
                                        </div>
                                    </div>

                                    <ul style="margin:0.75rem 0;padding-left:1.15rem;font-size:0.9rem;">
                                        @foreach($order->items as $item)
                                            <li>
                                                {{ $item->quantity }}&times; {{ $item->product_name_snapshot }} ({{ $item->variant_label_snapshot }})
                                                &mdash; <span class="tabular" style="font-family:var(--font-mono);">₱{{ number_format((float) $item->line_total, 2) }}</span>
                                                @if($item->custom_notes)<br><span>{{ $item->custom_notes }}</span>@endif
                                            </li>
                                        @endforeach
                                    </ul>

                                    <div style="display:flex;justify-content:space-between;flex-wrap:wrap;gap:0.5rem;font-family:var(--font-mono);font-size:0.8rem;color:var(--taupe-muted);">
                                        <span>Tracking: {{ $order->delivery?->tracking_number ?? 'Preparing' }} &middot; {{ $order->shipping_address_line }}, {{ $order->shipping_city }}</span>
                                        <strong class="tabular" style="color:var(--espresso-ink);">Total: ₱{{ number_format((float) $order->grand_total, 2) }}</strong>
                                    </div>

                                    @if(! in_array($order->status, ['cancelled', 'returned'], true))
                                        @php
                                            $steps = ['pending' => 1, 'confirmed' => 2, 'preparing' => 3, 'shipped' => 4, 'delivered' => 5];
                                            $idx = $steps[$order->status] ?? 1;
                                        @endphp
                                        <div class="timeline-bar">
                                            <div class="timeline-step {{ $idx >= 1 ? 'is-reached' : '' }}">Placed</div>
                                            <div class="timeline-step {{ $idx >= 2 ? 'is-reached' : '' }}">Confirmed</div>
                                            <div class="timeline-step {{ $idx >= 3 ? 'is-reached' : '' }}">Preparing</div>
                                            <div class="timeline-step {{ $idx >= 4 ? 'is-reached' : '' }}">Shipped</div>
                                            <div class="timeline-step {{ $idx >= 5 ? 'is-reached' : '' }}">Delivered</div>
                                        </div>
                                    @endif

                                    @if($order->returnRequests->isNotEmpty())
                                        <div style="margin-top:0.85rem;padding-top:0.65rem;border-top:1px dashed var(--espresso-ink);">
                                            @foreach($order->returnRequests as $ret)
                                                <div style="font-family:var(--font-mono);font-size:0.78rem;display:flex;justify-content:space-between;gap:0.5rem;flex-wrap:wrap;margin-top:0.25rem;">
                                                    <span>
                                                        <strong>{{ strtoupper(str_replace('_', ' ', $ret->request_type)) }}</strong>: {{ $ret->reason }}
                                                        @if($ret->restock_applied)
                                                            <em>(Merch restocked)</em>
                                                        @endif
                                                    </span>
                                                    <span class="status-tag status-tag--{{ $ret->status }}">{{ strtoupper($ret->status) }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    <div style="margin-top:0.9rem;display:flex;flex-wrap:wrap;gap:0.65rem;">
                                        @if($order->canBeCancelledByCustomer())
                                            <form method="POST" action="{{ route('account.orders.cancel', $order) }}" style="display:flex;gap:0.4rem;flex-wrap:wrap;width:100%;">
                                                @csrf
                                                <input type="text" name="reason" placeholder="Reason for cancelling before prep" class="field-control" style="flex:1;padding:0.42rem 0.65rem;">
                                                <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Cancel Pending Order</button>
                                            </form>
                                        @endif

                                        @if($order->canRequestReturnByCustomer())
                                            <details style="width:100%;border-top:1px solid var(--hairline);padding-top:0.65rem;">
                                                <summary style="cursor:pointer;font-family:var(--font-sans);font-size:0.74rem;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;">
                                                    Request Return or Spill Refund
                                                </summary>
                                                <form method="POST" action="{{ route('account.orders.return', $order) }}" class="form-stack" style="margin-top:0.75rem;">
                                                    @csrf
                                                    <div class="form-row-2">
                                                        <div class="field-group">
                                                            <label class="field-label">Item Covered</label>
                                                            <select name="order_item_id" class="field-control">
                                                                <option value="">Entire Order</option>
                                                                @foreach($order->items as $item)
                                                                    <option value="{{ $item->id }}">
                                                                        {{ $item->product_name_snapshot }} ({{ $item->is_perishable ? 'Drink · Spill Refund' : 'Merch · Restock Return' }})
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        <div class="field-group">
                                                            <label class="field-label">Request Type</label>
                                                            <select name="request_type" class="field-control" required>
                                                                <option value="return">Merch Return / Size Exchange</option>
                                                                <option value="spill_refund">Drink Quality / Transit Spill Refund</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                    <input type="text" name="reason" required placeholder="Short reason (e.g. Sizing swap or cup seal issue)" class="field-control">
                                                    <button type="submit" class="tag-btn tag-btn--sm">Submit Request</button>
                                                </form>
                                            </details>
                                        @endif
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            <div class="form-stack">
                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Profile Details</h2>
                    <form method="POST" action="{{ route('account.profile.update') }}" class="form-stack">
                        @csrf
                        @method('PATCH')
                        <div class="field-group">
                            <label class="field-label">Full Name</label>
                            <input type="text" name="name" value="{{ $user->name }}" class="field-control" required>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Phone</label>
                            <input type="text" name="phone" value="{{ $user->phone }}" class="field-control">
                        </div>
                        <button type="submit" class="tag-btn tag-btn--canopy tag-btn--sm">Save Profile</button>
                    </form>
                </div>

                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">Saved Delivery Addresses</h2>
                    @foreach($addresses as $addr)
                        <div style="border-bottom:1px solid var(--hairline);padding:0.65rem 0;display:flex;justify-content:space-between;gap:0.5rem;align-items:flex-start;">
                            <div>
                                <strong style="font-family:var(--font-sans);font-size:0.78rem;text-transform:uppercase;">{{ $addr->label }}</strong>
                                <div style="font-family:var(--font-mono);font-size:0.76rem;color:var(--taupe-muted);">
                                    {{ $addr->formattedAddress() }} &middot; {{ $addr->delivery_zone === 'local_express' ? 'Local Express' : 'Provincial Parcel' }}
                                </div>
                            </div>
                            <form method="POST" action="{{ route('account.addresses.delete', $addr) }}">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Delete</button>
                            </form>
                        </div>
                    @endforeach

                    <form method="POST" action="{{ route('account.addresses.store') }}" class="form-stack" style="margin-top:1rem;">
                        @csrf
                        <div class="form-row-2">
                            <input type="text" name="label" placeholder="Label (Home / Studio)" class="field-control" required>
                            <input type="text" name="recipient_name" value="{{ $user->name }}" placeholder="Recipient" class="field-control" required>
                        </div>
                        <div class="form-row-2">
                            <input type="text" name="phone" value="{{ $user->phone }}" placeholder="Phone" class="field-control" required>
                            <select name="delivery_zone" class="field-control">
                                <option value="local_express">Local Express</option>
                                <option value="provincial_parcel">Provincial Parcel</option>
                            </select>
                        </div>
                        <input type="text" name="street_address" placeholder="Street / House No." class="field-control" required>
                        <div class="form-row-2">
                            <input type="text" name="city" placeholder="City" class="field-control" required>
                            <input type="text" name="province" placeholder="Province" class="field-control" required>
                        </div>
                        <input type="text" name="postal_code" placeholder="Postal Code" class="field-control" required>
                        <button type="submit" class="tag-btn tag-btn--outline tag-btn--sm">Add Address</button>
                    </form>
                </div>

                <div class="ledger-panel">
                    <h2 class="ledger-panel__title">My Email Notifications ({{ $emailLogs->count() }})</h2>
                    @foreach($emailLogs as $mail)
                        <div style="border-bottom:1px solid var(--hairline);padding:0.55rem 0;font-family:var(--font-mono);font-size:0.78rem;">
                            <strong>{{ $mail->subject }}</strong>
                            <div style="color:var(--taupe-muted);">{{ $mail->sent_at?->format('M d, Y g:i A') }} &middot; {{ $mail->smtp_status }}</div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>
@endsection
