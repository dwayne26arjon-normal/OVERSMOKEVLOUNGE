<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orders | OversmokeVlounge</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/orders.css') }}?v={{ filemtime(public_path('css/orders.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/account-menu.css') }}?v={{ filemtime(public_path('css/account-menu.css')) }}">
    <style>
        .order-status.status-processing { border-color: rgba(212,160,45,.35); color: #e8c35f; background: rgba(160,117,18,.1); }
        .order-status.status-shipped { border-color: rgba(72,143,240,.35); color: #73adff; background: rgba(30,59,102,.2); }
        .order-status.status-completed { border-color: rgba(48,180,122,.3); color: #60d9a1; background: rgba(28,125,81,.1); }
        .order-status.status-cancelled { border-color: rgba(225,77,97,.35); color: #ff7888; background: rgba(151,34,52,.12); }
        .order-totals { display: grid; gap: 5px; margin-left: auto; min-width: 190px; }
        .order-totals div { display: flex; justify-content: space-between; gap: 12px; color: var(--muted); font-size: 10px; }
        .order-totals dt, .order-totals dd { margin: 0; }
        .order-totals .grand-total { color: var(--text); font-size: 12px; font-weight: 600; }
        .order-list-note { color: var(--muted); font-size: 10px; }
        .order-list-note[hidden] { display: none; }
        .order-cancel-form[hidden] { display: none; }
        .order-cancel-button { align-self: flex-start; padding: 8px 12px; border: 1px solid rgba(225,77,97,.4); border-radius: 6px; color: #ff9aa6; background: rgba(151,34,52,.12); font: inherit; font-size: 10px; cursor: pointer; }
        .order-cancel-button:hover { background: rgba(151,34,52,.24); }
        .order-page-status { margin: 0 0 16px; color: #60d9a1; font-size: 12px; }
    </style>
</head>
<body
    data-user-id="{{ auth('web')->id() }}"
    data-orders-updates-url="{{ route('orders.updates') }}"
>
    <div class="smoke"></div>
    <header class="topbar site-topbar">
        <a class="logo" href="{{ url('/home') }}">
            <img src="{{ asset('images/oversmokev-mascot.png') }}" alt="">
            <span>OVERSMOKE</span>
        </a>
        <nav class="site-nav">
            <a href="{{ url('/home') }}">Home</a>
            <a class="active" href="{{ route('orders') }}" aria-current="page">Orders</a>
        </nav>
        <div class="topbar-actions">
            <a class="cart-link" href="{{ route('cart') }}" aria-label="Cart">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
                <span class="cart-count" id="cartCount">0</span>
            </a>
            <x-user.account-menu />
        </div>
    </header>

    <main class="page">
        <a class="back-link" href="{{ url('/home') }}">← <span>Continue shopping</span></a>
        <div class="page-heading">
            <div>
                <p class="eyebrow">YOUR ACCOUNT</p>
                <h1>My Orders</h1>
            </div>
            <div class="heading-meta">
                <span class="order-count">{{ $orders->count() }} {{ \Illuminate\Support\Str::plural('order', $orders->count()) }}</span>
                <span class="storage-note">Saved to your account</span>
            </div>
        </div>
        @if (session('status'))
            <p class="order-page-status" role="status">{{ session('status') }}</p>
        @endif

        @if ($orders->isEmpty())
            <section class="empty-state">
                <div class="empty-icon" aria-hidden="true">▤</div>
                <h2>No orders yet</h2>
                <p>When you place an order, it will appear here.</p>
                <a href="{{ url('/home#products') }}">Browse products</a>
            </section>
        @else
            <section class="orders-list" aria-label="Order history">
                @foreach ($orders as $order)
                    <article class="order-card" id="order-{{ $order->id }}" data-order-id="{{ $order->id }}">
                        <header class="order-header">
                            <div class="order-reference">
                                <strong>{{ $order->order_number }}</strong>
                                <span class="order-date">Placed {{ $order->created_at->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}</span>
                            </div>
                            <span class="order-status status-{{ $order->status }}" data-order-status>{{ ucfirst($order->status) }}</span>
                        </header>
                        <div class="order-products">
                            @foreach ($order->items as $item)
                                <div class="order-product">
                                    <div class="order-product-info">
                                        <span class="product-thumbnail">
                                            @if ($item->image_path)
                                                <img src="{{ asset('storage/'.$item->image_path) }}" alt="">
                                            @else
                                                <span></span>
                                            @endif
                                        </span>
                                        <div class="product-info">
                                            <strong>{{ $item->product_name }}</strong>
                                            <span>{{ $item->brand ?: $item->category }} · {{ $item->color ?: 'Default' }} · Qty {{ $item->quantity }}</span>
                                        </div>
                                    </div>
                                    <strong class="product-total">₱{{ number_format((float) $item->unit_price * $item->quantity, 2) }}</strong>
                                </div>
                            @endforeach
                        </div>
                        <footer class="order-footer">
                            <span class="delivery-info">{{ $order->customer_phone }} · {{ $order->delivery_address }} · {{ $order->payment_method }}</span>
                            <dl class="order-totals">
                                <div><dt>Subtotal</dt><dd>₱{{ number_format((float) $order->subtotal, 2) }}</dd></div>
                                <div><dt>Shipping fee</dt><dd>₱{{ number_format((float) $order->shipping_fee, 2) }}</dd></div>
                                <div class="grand-total"><dt>Total</dt><dd>₱{{ number_format((float) $order->total, 2) }}</dd></div>
                            </dl>
                            <form class="order-cancel-form" data-order-cancel method="POST" action="{{ route('orders.cancel', $order) }}" onsubmit="return confirm('Cancel this order?')" @if ($order->status !== 'processing') hidden @endif>
                                @csrf
                                <button class="order-cancel-button" type="submit">Cancel order</button>
                            </form>
                        </footer>
                    </article>
                @endforeach
            </section>
        @endif
    </main>
    <script>
        const cartKey = `oversmoke-cart-user-${document.body.dataset.userId}`;
        document.getElementById('cartCount').textContent = JSON.parse(localStorage.getItem(cartKey) || '[]').length;
    </script>
    <script src="{{ asset('js/account-menu.js') }}?v={{ filemtime(public_path('js/account-menu.js')) }}"></script>
    <script src="{{ asset('js/user-orders-live.js') }}?v={{ filemtime(public_path('js/user-orders-live.js')) }}"></script>
</body>
</html>
