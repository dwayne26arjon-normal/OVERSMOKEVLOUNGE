@extends('admin.layout')

@section('title', 'Order '.$order->order_number)

@section('content')
    <div class="page-heading order-detail-heading">
        <div>
            <a class="back-link" href="{{ route('admin.orders.index') }}">← Back to orders</a>
            <h1>Order #{{ $order->order_number }}</h1>
        </div>
        <form
            class="status-form"
            id="order-status-form"
            method="POST"
            action="{{ route('admin.orders.status', $order) }}"
            data-order-number="{{ $order->order_number }}"
            data-current-status="{{ $order->status }}"
        >
            @csrf
            @method('PUT')
            <label class="sr-only" for="order-status">Order status</label>
            <select id="order-status" name="status">
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected($order->status === $status)>{{ ucfirst($status) }}</option>
                @endforeach
            </select>
            <button class="secondary-button" type="submit">Update status</button>
        </form>
    </div>

    @error('invoice')
        <div class="order-error" role="alert">{{ $message }}</div>
    @enderror

    @if ($order->status === 'processing')
        <aside class="order-review-checklist" data-order-review-checklist aria-labelledby="order-review-checklist-title">
            <span class="order-review-checklist-icon" aria-hidden="true">!</span>
            <div>
                <h2 id="order-review-checklist-title">Before updating this order, check these items</h2>
                <ul>
                    <li>Confirm the customer's contact information and delivery address.</li>
                    <li>Verify the ordered products, variants, quantities, and total.</li>
                    <li>Review the payment method and update the status to match the order's actual progress.</li>
                    <li>Cancelling an order returns its items to available inventory.</li>
                </ul>
            </div>
        </aside>
    @endif

    <section class="order-detail-grid">
        <article class="panel detail-card customer-card">
            <div class="detail-card-heading">
                <div>
                    <h2 class="panel-title">Order #{{ $order->order_number }}</h2>
                    <p class="panel-subtitle">Placed {{ $order->created_at->timezone(config('app.timezone'))->format('F j, Y \a\t g:i A') }}</p>
                </div>
                <span class="order-status-badge status-{{ $order->status }}">{{ ucfirst($order->status) }}</span>
            </div>

            <section class="detail-section">
                <h3>Customer information</h3>
                <p class="customer-name">{{ $order->customer_name }}</p>
                <a href="mailto:{{ $order->customer_email }}">{{ $order->customer_email }}</a>
                <a href="tel:{{ $order->customer_phone }}">{{ $order->customer_phone }}</a>
            </section>

            <section class="detail-section shipping-section">
                <h3>Shipping address</h3>
                <p>{{ $order->delivery_address }}</p>
                <span>{{ $order->customer_phone }}</span>
            </section>
            <div class="payment-note">Payment method: {{ $order->payment_method }}</div>
        </article>

        <article class="panel detail-card items-card">
            <div class="detail-card-heading">
                <div>
                    <h2 class="panel-title">Ordered items</h2>
                    <p class="panel-subtitle">{{ $order->items->count() }} {{ \Illuminate\Support\Str::plural('product', $order->items->count()) }} in this order</p>
                </div>
            </div>

            <div class="ordered-items">
                @foreach ($order->items as $item)
                    <div class="ordered-item">
                        <span class="ordered-item-image">
                            @if ($item->image_path)
                                <img src="{{ asset('storage/'.$item->image_path) }}" alt="">
                            @else
                                {{ \Illuminate\Support\Str::upper(\Illuminate\Support\Str::substr($item->product_name, 0, 1)) }}
                            @endif
                        </span>
                        <span class="ordered-item-copy">
                            <strong>{{ $item->product_name }}</strong>
                            <small>{{ $item->brand ?: $item->category }}{{ $item->color ? ' · '.$item->color : '' }}</small>
                            <small>₱{{ number_format((float) $item->unit_price, 2) }} × {{ $item->quantity }}</small>
                        </span>
                        <strong class="ordered-item-total">₱{{ number_format((float) $item->unit_price * $item->quantity, 2) }}</strong>
                    </div>
                @endforeach
            </div>

            <dl class="order-totals">
                <div><dt>Subtotal</dt><dd>₱{{ number_format((float) $order->subtotal, 2) }}</dd></div>
                <div><dt>Shipping fee</dt><dd>₱{{ number_format((float) $order->shipping_fee, 2) }}</dd></div>
                <div class="grand-total"><dt>Total</dt><dd>₱{{ number_format((float) $order->total, 2) }}</dd></div>
            </dl>

            <div class="invoice-actions">
                <a class="secondary-button" href="{{ route('admin.orders.invoice', $order) }}" target="_blank" rel="noopener">Print invoice</a>
                <form method="POST" action="{{ route('admin.orders.invoice.email', $order) }}">
                    @csrf
                    <button class="primary-button" type="submit">Email invoice</button>
                </form>
            </div>
        </article>
    </section>

    <dialog class="status-confirm-dialog" id="statusConfirmDialog" aria-labelledby="statusConfirmTitle" aria-describedby="statusConfirmMessage">
        <div class="status-confirm-icon" aria-hidden="true">!</div>
        <p class="status-confirm-eyebrow">CONFIRM ORDER UPDATE</p>
        <h2 id="statusConfirmTitle">Update order status?</h2>
        <p id="statusConfirmMessage"></p>
        <p class="status-confirm-warning" id="statusConfirmWarning" hidden></p>
        <div class="status-confirm-actions">
            <button type="button" class="status-confirm-cancel" id="cancelStatusUpdate">Keep current status</button>
            <button type="button" class="status-confirm-submit" id="confirmStatusUpdate">Confirm update</button>
        </div>
    </dialog>
@endsection

@push('styles')
    <style>
        .order-detail-heading { align-items: end; margin-bottom: 14px; }
        .order-detail-heading h1 { margin-top: 9px; font-size: 21px; }
        .back-link { color: #a995dc; font-size: 9px; text-decoration: none; }
        .status-form { display: flex; align-items: center; gap: 7px; }
        .status-form select { height: 31px; padding: 0 9px; border: 1px solid #302a3b; border-radius: 5px; background: #13111a; color: #eee; font: inherit; font-size: 9px; }
        .status-confirm-dialog { width: min(420px, calc(100% - 32px)); padding: 26px; border: 1px solid rgba(124,92,240,.5); border-radius: 14px; color: #f3efff; background: #15121c; text-align: center; box-shadow: 0 24px 80px rgba(0,0,0,.55), 0 0 32px rgba(124,92,240,.12); }
        .status-confirm-dialog::backdrop { background: rgba(5,4,10,.78); backdrop-filter: blur(5px); }
        .status-confirm-icon { display: grid; width: 42px; height: 42px; margin: 0 auto 12px; place-items: center; border: 1px solid rgba(232,195,95,.45); border-radius: 50%; color: #e8c35f; background: rgba(232,195,95,.1); font-size: 20px; font-weight: 700; }
        .status-confirm-eyebrow { margin: 0 0 6px; color: #b6a5ff; font-size: 9px; font-weight: 600; letter-spacing: 1.6px; }
        .status-confirm-dialog h2 { margin: 0; font-size: 18px; }
        .status-confirm-dialog #statusConfirmMessage { margin: 9px 0 0; color: #b8b2c1; font-size: 12px; line-height: 1.6; }
        .status-confirm-warning { margin: 12px 0 0; padding: 9px 10px; border: 1px solid rgba(232,195,95,.22); border-radius: 6px; color: #e8c35f; background: rgba(232,195,95,.07); font-size: 10px; line-height: 1.5; }
        .status-confirm-warning[hidden] { display: none; }
        .status-confirm-actions { display: flex; justify-content: center; gap: 9px; margin-top: 20px; }
        .status-confirm-actions button { min-height: 38px; padding: 0 14px; border: 1px solid #40384f; border-radius: 7px; color: #d6d0df; background: #1b1823; font: inherit; font-size: 10px; cursor: pointer; }
        .status-confirm-actions button:hover { border-color: #7862b5; background: #25202f; }
        .status-confirm-actions .status-confirm-submit { border-color: #7655df; color: #fff; background: #6232e8; font-weight: 600; }
        .status-confirm-actions .status-confirm-submit:hover { background: #7655f2; }
        .status-confirm-actions button:focus-visible { outline: 2px solid #c4b5fd; outline-offset: 3px; }
        .order-review-checklist { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 14px; padding: 14px; border: 1px solid rgba(212,160,45,.38); border-left: 3px solid #d4a02d; border-radius: 7px; background: linear-gradient(110deg, rgba(160,117,18,.14), rgba(160,117,18,.045)); }
        .order-review-checklist-icon { display: grid; width: 25px; height: 25px; flex: 0 0 25px; place-items: center; border: 1px solid rgba(232,195,95,.4); border-radius: 50%; color: #e8c35f; background: rgba(160,117,18,.18); font-size: 13px; font-weight: 700; }
        .order-review-checklist h2 { margin: 0 0 7px; color: #f0d788; font: 600 12px 'Outfit', sans-serif; }
        .order-review-checklist ul { display: grid; gap: 4px; margin: 0; padding-left: 16px; color: #c9c1ad; font-size: 10px; line-height: 1.5; }
        .order-detail-grid { display: grid; grid-template-columns: minmax(240px, .85fr) minmax(0, 1.15fr); gap: 14px; align-items: start; }
        .detail-card { min-width: 0; padding: 15px; }
        .detail-card-heading { display: flex; align-items: flex-start; justify-content: space-between; gap: 10px; padding-bottom: 11px; border-bottom: 1px solid #2b2634; }
        .detail-card .panel-title { font-size: 12px; }
        .detail-card .panel-subtitle { margin: 4px 0 0; color: #817b8a; font-size: 8px; }
        .order-status-badge { display: inline-flex; min-height: 19px; align-items: center; padding: 0 7px; border: 1px solid #312943; border-radius: 4px; background: #1c1725; color: #c4b1ff; font-size: 8px; white-space: nowrap; }
        .status-processing { border-color: rgba(212,160,45,.28); background: rgba(160,117,18,.1); color: #e8c35f; }
        .status-shipped { border-color: rgba(72,143,240,.3); background: rgba(30,59,102,.2); color: #73adff; }
        .status-completed { border-color: rgba(48,180,122,.24); background: rgba(28,125,81,.1); color: #60d9a1; }
        .status-cancelled { border-color: rgba(225,77,97,.27); background: rgba(151,34,52,.12); color: #ff7888; }
        .detail-section { display: grid; gap: 7px; padding: 14px 0; }
        .detail-section h3 { margin: 0 0 2px; font-size: 11px; }
        .detail-section p, .detail-section a, .detail-section span { margin: 0; color: #c4bfcb; font-size: 9px; overflow-wrap: anywhere; }
        .detail-section a { color: #b6a5ff; text-decoration: none; }
        .customer-name { color: #f0edf5 !important; font-size: 11px !important; font-weight: 600; }
        .shipping-section { border-top: 1px solid #2b2634; }
        .payment-note { padding-top: 9px; border-top: 1px solid #2b2634; color: #898393; font-size: 8px; }
        .ordered-items { display: grid; }
        .ordered-item { display: flex; min-width: 0; align-items: center; gap: 9px; padding: 10px 0; border-bottom: 1px solid rgba(43,38,52,.66); }
        .ordered-item-image { display: grid; width: 53px; height: 53px; flex: 0 0 53px; place-items: center; overflow: hidden; border: 1px solid #322c3e; border-radius: 4px; background: #15121c; color: #8d829f; font-size: 12px; }
        .ordered-item-image img { width: 100%; height: 100%; object-fit: contain; }
        .ordered-item-copy { display: grid; min-width: 0; flex: 1; gap: 3px; }
        .ordered-item-copy strong { overflow: hidden; color: #eeeaf5; font-size: 9px; text-overflow: ellipsis; white-space: nowrap; }
        .ordered-item-copy small { color: #8f8998; font-size: 8px; }
        .ordered-item-total { color: #eeeaf5; font-size: 9px; white-space: nowrap; }
        .order-totals { display: grid; gap: 9px; margin: 12px 0; }
        .order-totals div { display: flex; justify-content: space-between; gap: 10px; color: #b9b3c2; font-size: 9px; }
        .order-totals dt, .order-totals dd { margin: 0; }
        .order-totals .grand-total { padding-top: 9px; border-top: 1px solid #2b2634; color: #f4f1ff; font-weight: 700; }
        .invoice-actions { display: flex; justify-content: flex-end; gap: 8px; padding-top: 10px; border-top: 1px solid #2b2634; }
        .invoice-actions form { margin: 0; }
        .order-error { margin-bottom: 12px; padding: 9px 11px; border: 1px solid rgba(225,77,97,.3); border-radius: 6px; background: rgba(151,34,52,.12); color: #ff9aa7; font-size: 10px; }
        @media (max-width: 760px) { .order-detail-grid { grid-template-columns: 1fr; } }
        @media (max-width: 480px) { .order-detail-heading { align-items: flex-start; flex-direction: column; }.status-form { width: 100%; }.invoice-actions { justify-content: stretch; }.invoice-actions > *, .invoice-actions form, .invoice-actions button { flex: 1; }.invoice-actions .primary-button, .invoice-actions .secondary-button { width: 100%; } }
    </style>
@endpush

@push('scripts')
    <script>
        const orderStatusForm = document.getElementById('order-status-form');
        const orderStatusSelect = document.getElementById('order-status');
        const statusConfirmDialog = document.getElementById('statusConfirmDialog');
        const statusConfirmWarning = document.getElementById('statusConfirmWarning');
        let confirmedStatusUpdate = false;

        orderStatusForm.addEventListener('submit', event => {
            if (confirmedStatusUpdate) {
                confirmedStatusUpdate = false;
                return;
            }

            const previousStatus = orderStatusForm.dataset.currentStatus;
            const nextStatus = orderStatusSelect.value;

            if (previousStatus === nextStatus) {
                event.preventDefault();
                orderStatusSelect.value = previousStatus;
                return;
            }

            const orderNumber = orderStatusForm.dataset.orderNumber;
            const currentLabel = previousStatus.charAt(0).toUpperCase() + previousStatus.slice(1);
            const nextLabel = nextStatus.charAt(0).toUpperCase() + nextStatus.slice(1);
            document.getElementById('statusConfirmMessage').textContent =
                `Update order ${orderNumber} from ${currentLabel} to ${nextLabel}?`;

            if (nextStatus === 'cancelled') {
                statusConfirmWarning.textContent = 'Cancelling this order will return its items to available inventory.';
                statusConfirmWarning.hidden = false;
            } else if (previousStatus === 'cancelled') {
                statusConfirmWarning.textContent = 'Reopening this order will reserve its items from available inventory.';
                statusConfirmWarning.hidden = false;
            } else {
                statusConfirmWarning.textContent = '';
                statusConfirmWarning.hidden = true;
            }

            event.preventDefault();
            statusConfirmDialog.showModal();
        });

        document.getElementById('cancelStatusUpdate').addEventListener('click', () => statusConfirmDialog.close());
        document.getElementById('confirmStatusUpdate').addEventListener('click', () => {
            confirmedStatusUpdate = true;
            statusConfirmDialog.close();
            orderStatusForm.requestSubmit();
        });
        statusConfirmDialog.addEventListener('close', () => {
            if (! confirmedStatusUpdate) orderStatusSelect.value = orderStatusForm.dataset.currentStatus;
        });
    </script>
@endpush
