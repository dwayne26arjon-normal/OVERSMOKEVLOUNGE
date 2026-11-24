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
        .order-totals { display: grid; grid-column: 1 / -1; width: 100%; min-width: 0; gap: 5px; margin: 0; }
        .order-totals div { display: flex; justify-content: space-between; gap: 12px; color: var(--muted); font-size: 10px; }
        .order-totals dt, .order-totals dd { margin: 0; }
        .order-totals .grand-total { color: var(--text); font-size: 12px; font-weight: 600; }
        .order-list-note { color: var(--muted); font-size: 10px; }
        .order-list-note[hidden] { display: none; }
        .order-cancel-form[hidden] { display: none; }
        .order-footer { display: grid; grid-template-columns: minmax(0, 1fr); align-items: stretch; gap: 12px; }
        .order-footer > .delivery-info { grid-column: 1 / -1; min-width: 0; }
        .order-cancel-form { justify-self: start; width: max-content; max-width: 100%; margin: 0; }
        .order-cancel-button { display: inline-flex; width: max-content; max-width: 100%; min-width: max-content; align-items: center; justify-content: center; padding: 8px 12px; border: 1px solid rgba(225,77,97,.4); border-radius: 6px; color: #ff9aa6; background: rgba(151,34,52,.12); font: inherit; font-size: 10px; white-space: nowrap; word-break: keep-all; cursor: pointer; }
        .order-cancel-button:hover { background: rgba(151,34,52,.24); }
        .order-page-status { margin: 0 0 16px; color: #60d9a1; font-size: 12px; }
        .order-cancel-dialog { width: min(420px, calc(100% - 32px)); margin: auto; padding: 24px; border: 1px solid rgba(225,77,97,.4); border-radius: 14px; color: var(--text); background: #11111b; box-shadow: 0 24px 80px rgba(0,0,0,.55); }
        .order-cancel-dialog::backdrop { background: rgba(5,5,12,.78); backdrop-filter: blur(6px); }
        .order-cancel-dialog h2 { margin: 0; font-size: 18px; }
        .order-cancel-dialog p { margin: 9px 0 0; color: var(--muted); font-size: 12px; line-height: 1.6; }
        .order-cancel-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 20px; }
        .order-cancel-actions button { min-height: 36px; padding: 0 13px; border: 1px solid var(--line); border-radius: 7px; font: inherit; font-size: 11px; }
        .order-cancel-keep { color: var(--text); background: rgba(255,255,255,.04); }
        .order-cancel-confirm { border-color: rgba(225,77,97,.5) !important; color: #fff; background: #9c3043; }
        .order-cancel-actions button:focus-visible { outline: 2px solid #a78bfa; outline-offset: 3px; }
        .refund-request-details { grid-column: 1 / -1; width: 100%; min-width: 0; padding: 12px; border: 1px solid rgba(225,77,97,.3); border-radius: 9px; background: linear-gradient(135deg, rgba(151,34,52,.11), rgba(17,17,27,.88)); box-shadow: inset 0 1px rgba(255,255,255,.025); transition: border-color .2s, background .2s; }
        .refund-request-details[open] { border-color: rgba(225,77,97,.55); background: linear-gradient(135deg, rgba(151,34,52,.16), rgba(17,17,27,.96)); }
        .refund-request-details summary { display: flex; min-height: 38px; align-items: center; gap: 9px; padding: 0; border: 0; color: #ffb1b8; background: transparent; font-size: 11px; font-weight: 600; list-style: none; cursor: pointer; }
        .refund-request-details summary:focus-visible { border-radius: 4px; outline: 2px solid rgba(255,177,184,.75); outline-offset: 3px; }
        .refund-request-details summary::-webkit-details-marker { display: none; }
        .refund-request-details summary::before { display: grid; width: 23px; height: 23px; flex: 0 0 23px; place-items: center; border: 1px solid rgba(225,77,97,.4); border-radius: 50%; background: rgba(151,34,52,.2); content: "+"; font-size: 16px; line-height: 1; }
        .refund-request-details[open] summary::before { content: "−"; }
        .refund-request-form { display: grid; width: 100%; min-width: 0; gap: 9px; max-width: 640px; margin-top: 11px; padding-top: 12px; border-top: 1px solid rgba(225,77,97,.2); }
        .refund-request-form label { color: #d7d0de; font-size: 10px; font-weight: 500; }
        .refund-request-form textarea, .refund-request-form input[type="file"] { width: 100%; padding: 10px; border: 1px solid #39313e; border-radius: 6px; background: rgba(9,8,14,.72); color: var(--text); font: inherit; font-size: 11px; }
        .refund-request-form textarea:focus, .refund-request-form input[type="file"]:focus { border-color: rgba(225,77,97,.7); outline: 2px solid rgba(225,77,97,.16); }
        .refund-request-form textarea { min-height: 100px; resize: vertical; }
        .refund-file-picker { display: flex; min-height: 44px; align-items: center; gap: 9px; padding: 6px; border: 1px solid #39313e; border-radius: 6px; background: rgba(9,8,14,.72); }
        .refund-file-picker input[type="file"] { position: absolute; width: 1px; height: 1px; margin: -1px; padding: 0; overflow: hidden; border: 0; clip: rect(0, 0, 0, 0); clip-path: inset(50%); white-space: nowrap; }
        .refund-file-picker-button { display: flex; width: 100%; min-height: 32px; align-items: center; gap: 9px; padding: 0; border: 0; border-radius: 4px; background: transparent; cursor: pointer; }
        .refund-file-picker-button:hover { background: rgba(151,34,52,.08); }
        .refund-file-picker-plus { display: inline-flex; width: 34px; height: 34px; flex: 0 0 34px; align-items: center; justify-content: center; border: 1px solid rgba(225,77,97,.45); border-radius: 6px; color: #ffb1b8; background: rgba(151,34,52,.2); font-size: 23px; line-height: 1; transition: background .2s, border-color .2s; }
        .refund-file-picker-button:hover .refund-file-picker-plus { border-color: rgba(255,177,184,.75); background: rgba(151,34,52,.35); }
        .refund-file-picker input[type="file"]:focus-visible + .refund-file-picker-button { outline: 2px solid rgba(255,177,184,.75); outline-offset: 3px; }
        .refund-file-name { min-width: 0; color: var(--muted); font-size: 10px; overflow-wrap: anywhere; }
        .refund-upload-previews { display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 8px; }
        .refund-upload-previews:empty { display: none; }
        .refund-upload-preview { display: grid; gap: 5px; margin: 0; padding: 6px; border: 1px solid var(--line); border-radius: 6px; background: #11111b; }
        .refund-upload-preview img, .refund-upload-preview video { display: block; width: 100%; max-height: 180px; aspect-ratio: 4 / 3; border-radius: 4px; background: #09080d; object-fit: contain; }
        .refund-upload-preview figcaption { color: var(--muted); font-size: 9px; }
        .refund-payment-qr { display: grid; gap: 8px; padding: 10px; border: 1px solid rgba(167,139,250,.24); border-radius: 7px; background: rgba(91,63,145,.08); }
        .refund-payment-qr > label { color: #d7cbff; }
        .refund-request-help { margin: -4px 0 0; color: var(--muted); font-size: 9px; line-height: 1.5; }
        .refund-submit-button { justify-self: start; min-height: 40px; padding: 0 16px; border: 1px solid rgba(255,140,155,.45); border-radius: 6px; color: #fff; background: linear-gradient(110deg, #842d40, #a1384c); box-shadow: 0 4px 14px rgba(151,34,52,.2); font: inherit; font-size: 10px; font-weight: 600; cursor: pointer; transition: filter .2s, transform .2s, box-shadow .2s; }
        .refund-submit-button:hover { filter: brightness(1.12); box-shadow: 0 6px 18px rgba(151,34,52,.32); transform: translateY(-1px); }
        .refund-request-status { display: grid; grid-column: 1 / -1; width: 100%; min-width: 0; gap: 5px; padding: 12px; border: 1px solid var(--line); border-radius: 8px; color: var(--muted); background: rgba(255,255,255,.025); font-size: 10px; overflow-wrap: anywhere; }
        .refund-request-status strong { color: #e8c35f; }
        .refund-request-status.status-approved strong, .refund-request-status.status-refunded strong { color: #60d9a1; }
        .refund-request-status.status-rejected strong { color: #ff7888; }
        .refund-evidence-list { display: flex; flex-wrap: wrap; gap: 8px; }
        .refund-evidence-list a { color: #c4b1ff; text-decoration: underline; }
        .refund-errors { margin: 0 0 14px; padding: 10px 12px; border: 1px solid rgba(225,77,97,.35); border-radius: 7px; color: #ff9aa6; background: rgba(151,34,52,.12); font-size: 11px; }
        @media (max-width: 800px) {
            .refund-submit-button { width: 100%; }
        }
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
        @if ($errors->any())
            <div class="refund-errors" role="alert">
                @foreach ($errors->all() as $error)
                    <p>{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if ($orders->isEmpty())
            <section class="empty-state">
                <div class="empty-icon" aria-hidden="true">▤</div>
                <h2>No orders yet</h2>
                <p>When you place an order, it will appear here.</p>
                <a href="{{ url('/home#products') }}">Browse products</a>
            </section>
        @else
            <div class="order-filters" role="group" aria-label="Filter orders by status">
                <button class="order-filter" type="button" data-order-filter="all" aria-pressed="true">
                    All <span class="order-filter-count" data-order-filter-count="all">{{ $orders->count() }}</span>
                </button>
                <button class="order-filter" type="button" data-order-filter="processing" aria-pressed="false">
                    To Ship <span class="order-filter-count" data-order-filter-count="processing">{{ $orders->where('status', 'processing')->count() }}</span>
                </button>
                <button class="order-filter" type="button" data-order-filter="shipped" aria-pressed="false">
                    To Receive <span class="order-filter-count" data-order-filter-count="shipped">{{ $orders->where('status', 'shipped')->count() }}</span>
                </button>
                <button class="order-filter" type="button" data-order-filter="completed" aria-pressed="false">
                    Completed <span class="order-filter-count" data-order-filter-count="completed">{{ $orders->where('status', 'completed')->count() }}</span>
                </button>
                <button class="order-filter" type="button" data-order-filter="cancelled" aria-pressed="false">
                    Cancelled <span class="order-filter-count" data-order-filter-count="cancelled">{{ $orders->where('status', 'cancelled')->count() }}</span>
                </button>
            </div>
            <section class="orders-list" aria-label="Order history">
                @foreach ($orders as $order)
                    <article class="order-card" id="order-{{ $order->id }}" data-order-id="{{ $order->id }}" data-order-state="{{ $order->status }}">
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
                            <form class="order-cancel-form" data-order-cancel method="POST" action="{{ route('orders.cancel', $order) }}" @if ($order->status !== 'processing' || $order->refundRequest) hidden @endif>
                                @csrf
                                <button class="order-cancel-button" type="submit">Cancel order</button>
                            </form>
                            @if ($order->refundRequest)
                                <div class="refund-request-status status-{{ $order->refundRequest->status }}" aria-label="Refund request status">
                                    <span>Refund request: <strong>{{ ucfirst($order->refundRequest->status) }}</strong></span>
                                    @if ($order->refundRequest->approved_amount)
                                        <span>Approved amount: ₱{{ number_format((float) $order->refundRequest->approved_amount, 2) }}</span>
                                    @endif
                                    <span>{{ $order->refundRequest->description }}</span>
                                    @if ($order->refundRequest->admin_note)
                                        <span>Store response: {{ $order->refundRequest->admin_note }}</span>
                                    @endif
                                    @if ($order->refundRequest->status === 'refunded' && $order->refundRequest->completed_at)
                                        <span>The store recorded the manual refund as completed {{ $order->refundRequest->completed_at->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}. No payment is processed by this website.</span>
                                    @endif
                                    @if ($order->refundRequest->evidences->isNotEmpty())
                                        <div class="refund-evidence-list">
                                            @foreach ($order->refundRequest->evidences as $evidence)
                                                <a href="{{ route('refund-evidence.download', $evidence) }}">{{ $evidence->purpose === 'payment_qr' ? 'Download e-wallet QR code' : 'Download evidence '.$loop->iteration }}</a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @elseif ($order->status === 'completed')
                                <details class="refund-request-details" @if ((int) old('refund_order_id') === $order->id) open @endif>
                                    <summary>Request a refund</summary>
                                    <form class="refund-request-form" method="POST" action="{{ route('orders.refunds.store', $order) }}" enctype="multipart/form-data">
                                        @csrf
                                        <input type="hidden" name="refund_order_id" value="{{ $order->id }}">
                                        <label for="refund-description-{{ $order->id }}">Describe the damage and identify the affected item(s)</label>
                                        <textarea id="refund-description-{{ $order->id }}" name="description" maxlength="2000" required placeholder="Tell us what arrived damaged and which item was affected.">{{ (int) old('refund_order_id') === $order->id ? old('description') : '' }}</textarea>
                                        <label for="refund-attachments-{{ $order->id }}">Photos or video (optional)</label>
                                        <div class="refund-file-picker">
                                            <input id="refund-attachments-{{ $order->id }}" type="file" name="attachments[]" accept="image/jpeg,image/png,image/webp,video/mp4,video/quicktime,video/webm" multiple data-refund-attachments aria-label="Choose photos or video evidence">
                                            <label class="refund-file-picker-button" for="refund-attachments-{{ $order->id }}" title="Add photos or videos">
                                                <span class="refund-file-picker-plus" aria-hidden="true">+</span>
                                                <span class="refund-file-name" data-refund-file-name>No files selected</span>
                                            </label>
                                        </div>
                                        <div class="refund-upload-previews" data-refund-preview aria-live="polite"></div>
                                        <div class="refund-payment-qr">
                                            <label for="refund-payment-qr-{{ $order->id }}">E-wallet QR code (optional)</label>
                                            <div class="refund-file-picker">
                                                <input id="refund-payment-qr-{{ $order->id }}" type="file" name="payment_qr" accept="image/jpeg,image/png,image/webp" data-refund-payment-qr aria-label="Choose e-wallet QR code image">
                                                <label class="refund-file-picker-button" for="refund-payment-qr-{{ $order->id }}" title="Add e-wallet QR code">
                                                    <span class="refund-file-picker-plus" aria-hidden="true">+</span>
                                                    <span class="refund-file-name" data-refund-payment-qr-file-name>No file selected</span>
                                                </label>
                                            </div>
                                            <div class="refund-upload-previews" data-refund-payment-qr-preview aria-live="polite"></div>
                                            <p class="refund-request-help">Add a clear QR image if you would like to receive an approved refund through your e-wallet. This private image is visible only to you and authorized store staff; the store will arrange payment manually.</p>
                                        </div>
                                        <p class="refund-request-help">Up to 3 files; JPG, PNG, WebP, MP4, MOV, or WebM, up to 12 MB each. Attachments are private and visible only to you and store staff. One request per order.</p>
                                        <button class="refund-submit-button" type="submit">Submit refund request</button>
                                    </form>
                                </details>
                            @endif
                        </footer>
                    </article>
                @endforeach
            </section>
            <p class="filtered-empty" data-orders-filter-empty role="status" hidden>No orders in this category yet.</p>
        @endif
    </main>
    <dialog class="order-cancel-dialog" id="orderCancelDialog" aria-labelledby="orderCancelTitle" aria-describedby="orderCancelMessage">
        <h2 id="orderCancelTitle">Cancel this order?</h2>
        <p id="orderCancelMessage">This will cancel your order and return its items to stock.</p>
        <div class="order-cancel-actions">
            <button class="order-cancel-keep" id="keepOrderButton" type="button" autofocus>Keep order</button>
            <button class="order-cancel-confirm" id="confirmOrderCancelButton" type="button">Yes, cancel order</button>
        </div>
    </dialog>
    <script>
        const cartKey = `oversmoke-cart-user-${document.body.dataset.userId}`;
        document.getElementById('cartCount').textContent = JSON.parse(localStorage.getItem(cartKey) || '[]').length;

        const orderCancelDialog = document.getElementById('orderCancelDialog');
        let pendingOrderCancelForm = null;

        document.querySelectorAll('[data-order-cancel]').forEach(form => {
            form.addEventListener('submit', event => {
                event.preventDefault();
                if (typeof orderCancelDialog.showModal !== 'function') {
                    if (window.confirm('Cancel this order?')) form.submit();
                    return;
                }

                pendingOrderCancelForm = form;
                orderCancelDialog.showModal();
            });
        });

        document.querySelectorAll('[data-refund-attachments]').forEach(input => {
            const previewContainer = input.closest('form').querySelector('[data-refund-preview]');
            const fileName = input.closest('form').querySelector('[data-refund-file-name]');
            let previewUrls = [];

            input.addEventListener('change', () => {
                const fileCount = input.files?.length || 0;
                fileName.textContent = fileCount
                    ? `${fileCount} file${fileCount === 1 ? '' : 's'} selected`
                    : 'No files selected';
                previewUrls.forEach(url => URL.revokeObjectURL(url));
                previewUrls = [];
                previewContainer.replaceChildren();

                Array.from(input.files || []).forEach((file, index) => {
                    if (!file.type.startsWith('image/') && !file.type.startsWith('video/')) return;

                    const url = URL.createObjectURL(file);
                    previewUrls.push(url);
                    const preview = document.createElement('figure');
                    preview.className = 'refund-upload-preview';

                    if (file.type.startsWith('image/')) {
                        const image = document.createElement('img');
                        image.src = url;
                        image.alt = `Selected refund photo ${index + 1}`;
                        preview.append(image);
                    } else {
                        const video = document.createElement('video');
                        video.src = url;
                        video.controls = true;
                        video.playsInline = true;
                        video.preload = 'metadata';
                        video.setAttribute('aria-label', `Selected refund video ${index + 1}`);
                        preview.append(video);
                    }

                    const caption = document.createElement('figcaption');
                    caption.textContent = file.type.startsWith('image/')
                        ? `Photo preview ${index + 1}`
                        : `Video preview ${index + 1}`;
                    preview.append(caption);
                    previewContainer.append(preview);
                });
            });
        });

        document.querySelectorAll('[data-refund-payment-qr]').forEach(input => {
            const previewContainer = input.closest('form').querySelector('[data-refund-payment-qr-preview]');
            const fileName = input.closest('form').querySelector('[data-refund-payment-qr-file-name]');
            let previewUrl = null;

            input.addEventListener('change', () => {
                fileName.textContent = input.files?.[0]?.name || 'No file selected';
                if (previewUrl) URL.revokeObjectURL(previewUrl);
                previewUrl = null;
                previewContainer.replaceChildren();

                const file = input.files?.[0];
                if (!file || !file.type.startsWith('image/')) return;

                previewUrl = URL.createObjectURL(file);
                const preview = document.createElement('figure');
                preview.className = 'refund-upload-preview';
                const image = document.createElement('img');
                image.src = previewUrl;
                image.alt = 'Selected e-wallet QR code';
                preview.append(image);

                const caption = document.createElement('figcaption');
                caption.textContent = 'E-wallet QR preview';
                preview.append(caption);
                previewContainer.append(preview);
            });
        });

        document.getElementById('keepOrderButton').addEventListener('click', () => orderCancelDialog.close());
        document.getElementById('confirmOrderCancelButton').addEventListener('click', () => {
            if (!pendingOrderCancelForm) return;

            const form = pendingOrderCancelForm;
            pendingOrderCancelForm = null;
            form.submit();
        });
        orderCancelDialog.addEventListener('close', () => {
            pendingOrderCancelForm = null;
        });
        orderCancelDialog.addEventListener('click', event => {
            if (event.target === orderCancelDialog) orderCancelDialog.close();
        });
    </script>
    <script src="{{ asset('js/account-menu.js') }}?v={{ filemtime(public_path('js/account-menu.js')) }}"></script>
    <script src="{{ asset('js/user-orders-live.js') }}?v={{ filemtime(public_path('js/user-orders-live.js')) }}"></script>
</body>
</html>
