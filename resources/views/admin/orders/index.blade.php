@extends('admin.layout')

@section('title', 'Orders')

@section('content')
    <div class="page-heading orders-heading">
        <div>
            <h1>Customer orders</h1>
            <p>Review and manage orders placed by customers.</p>
        </div>
        <details class="sales-export-dropdown">
            <summary class="secondary-button">Download CSV <span aria-hidden="true">▾</span></summary>
            <div class="sales-export-menu">
                <a href="{{ route('admin.orders.sales.export', ['range' => 'day']) }}">Today</a>
                <a href="{{ route('admin.orders.sales.export', ['range' => 'month']) }}">This month</a>
                <a href="{{ route('admin.orders.sales.export', ['range' => 'year']) }}">This year</a>
            </div>
        </details>
    </div>

    <section class="panel orders-panel">
        <div class="orders-toolbar">
            <form class="orders-filters" method="GET" action="{{ route('admin.orders.index') }}">
                <label class="sr-only" for="order-search">Search orders or customers</label>
                <input id="order-search" type="search" name="q" value="{{ request('q') }}" placeholder="Search order or customer...">
                <label class="sr-only" for="order-status-filter">Filter by order status</label>
                <select id="order-status-filter" name="status" onchange="this.form.requestSubmit()">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
                <button class="secondary-button" type="submit">Filter</button>
                @if (request()->hasAny(['q', 'status']))
                    <a class="table-action" href="{{ route('admin.orders.index') }}">Clear</a>
                @endif
            </form>
        </div>

        @if ($orders->isEmpty())
            <div class="empty-state">
                {{ request()->hasAny(['q', 'status']) ? 'No orders match these filters.' : 'No customer orders yet. Orders will appear here after customers complete checkout.' }}
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table orders-table">
                    <thead>
                        <tr><th>ORDER</th><th>CUSTOMER</th><th>DATE</th><th>ITEMS</th><th>TOTAL</th><th>STATUS</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($orders as $order)
                        <tr>
                            <td><a class="order-number" href="{{ route('admin.orders.show', $order) }}">#{{ $order->order_number }}</a></td>
                            <td><span class="product-name">{{ $order->customer_name }}</span><span class="product-meta">{{ $order->customer_email }}</span></td>
                            <td>{{ $order->created_at->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}</td>
                            <td>{{ $order->items_count }} {{ \Illuminate\Support\Str::plural('item', $order->items_count) }}</td>
                            <td>₱{{ number_format((float) $order->total, 2) }}</td>
                            <td><span class="order-status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span></td>
                            <td><a class="table-action" href="{{ route('admin.orders.show', $order) }}">View details</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination">
                <span>Showing {{ $orders->firstItem() }}-{{ $orders->lastItem() }} of {{ $orders->total() }} orders</span>
                @if ($orders->hasPages())
                    {{ $orders->links() }}
                @endif
            </div>
        @endif
    </section>
@endsection

@push('styles')
    <style>
        .orders-panel { overflow: hidden; }
        .orders-heading { align-items: center; flex-wrap: wrap; }
        .orders-toolbar { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 13px; border-bottom: 1px solid #26212f; }
        .orders-filters { display: flex; align-items: center; gap: 8px; flex: 1; }
        .orders-filters input, .orders-filters select { height: 31px; padding: 0 10px; border: 1px solid #302a3b; border-radius: 5px; outline: 0; background: #13111a; color: #ded9e7; font: inherit; font-size: 9px; }
        .orders-filters input { width: min(280px, 42vw); }
        .orders-filters input:focus, .orders-filters select:focus { border-color: #7045eb; }
        .orders-filters select { min-width: 135px; }
        .orders-filters .secondary-button { min-height: 31px; }
        .sales-export-dropdown { position: relative; }
        .sales-export-dropdown > summary { min-height: 32px; cursor: pointer; list-style: none; }
        .sales-export-dropdown > summary::-webkit-details-marker { display: none; }
        .sales-export-dropdown > summary span { margin-left: 3px; color: #a99dbd; }
        .sales-export-menu { position: absolute; z-index: 10; top: calc(100% + 6px); right: 0; min-width: 150px; padding: 5px; border: 1px solid #302a3b; border-radius: 6px; background: #17141f; box-shadow: 0 10px 24px rgba(0,0,0,.35); }
        .sales-export-menu a { display: block; padding: 8px 10px; border-radius: 4px; color: #ded9e7; font-size: 10px; text-decoration: none; }
        .sales-export-menu a:hover, .sales-export-menu a:focus-visible { outline: 0; background: #262033; color: #fff; }
        .orders-table { min-width: 700px; }
        .order-number { color: #c4b1ff; font-weight: 600; text-decoration: none; }
        .order-number:hover { color: #fff; }
        .order-status-badge { display: inline-flex; min-height: 19px; align-items: center; padding: 0 7px; border: 1px solid #312943; border-radius: 4px; background: #1c1725; color: #c4b1ff; font-size: 9px; }
        .status-processing { border-color: rgba(212,160,45,.28); background: rgba(160,117,18,.1); color: #e8c35f; }
        .status-shipped { border-color: rgba(72,143,240,.3); background: rgba(30,59,102,.2); color: #73adff; }
        .status-completed { border-color: rgba(48,180,122,.24); background: rgba(28,125,81,.1); color: #60d9a1; }
        .status-cancelled { border-color: rgba(225,77,97,.27); background: rgba(151,34,52,.12); color: #ff7888; }
        @media (max-width: 620px) { .orders-filters { flex-wrap: wrap; }.orders-filters input { width: 100%; flex-basis: 100%; }.orders-filters select { flex: 1; } }
    </style>
@endpush
