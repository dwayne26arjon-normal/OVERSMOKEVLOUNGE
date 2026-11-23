@extends('admin.layout')

@section('title', 'Refund request '.$refundRequest->order->order_number)

@section('content')
    <div class="page-heading refund-heading">
        <div>
            <a class="refund-back-link" href="{{ route('admin.refunds.index') }}">← Back to refund requests</a>
            <h1>Refund request · {{ $refundRequest->order->order_number }}</h1>
            <p>Submitted {{ $refundRequest->created_at->timezone(config('app.timezone'))->format('F j, Y \a\t g:i A') }}</p>
        </div>
        <span class="refund-status status-{{ $refundRequest->status }}">{{ ucfirst($refundRequest->status) }}</span>
    </div>

    @if (session('status'))
        <div class="refund-flash" role="status">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="refund-error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <div class="refund-detail-grid">
        <section class="panel refund-detail-card">
            <h2>Customer and order</h2>
            <dl class="refund-facts">
                <div><dt>Customer</dt><dd>{{ $refundRequest->customer->name }}</dd></div>
                <div><dt>Email</dt><dd><a href="mailto:{{ $refundRequest->customer->email }}">{{ $refundRequest->customer->email }}</a></dd></div>
                <div><dt>Order</dt><dd><a href="{{ route('admin.orders.show', $refundRequest->order) }}">{{ $refundRequest->order->order_number }}</a></dd></div>
                <div><dt>Order status</dt><dd>{{ ucfirst($refundRequest->order->status) }}</dd></div>
                <div><dt>Order total</dt><dd>₱{{ number_format((float) $refundRequest->order->total, 2) }}</dd></div>
                @if ($refundRequest->approved_amount)
                    <div><dt>Approved refund amount</dt><dd>₱{{ number_format((float) $refundRequest->approved_amount, 2) }}</dd></div>
                @endif
            </dl>
            <h3>Ordered items</h3>
            <ul class="refund-items">
                @foreach ($refundRequest->order->items as $item)
                    <li>{{ $item->product_name }}{{ $item->color ? ' · '.$item->color : '' }} × {{ $item->quantity }} — ₱{{ number_format((float) $item->unit_price * $item->quantity, 2) }}</li>
                @endforeach
            </ul>
        </section>

        <section class="panel refund-detail-card">
            <h2>Damage description</h2>
            <p class="refund-description">{{ $refundRequest->description }}</p>

            <h3>Evidence ({{ $refundRequest->evidences->count() }})</h3>
            @if ($refundRequest->evidences->isEmpty())
                <p class="refund-muted">No files were attached.</p>
            @else
                <ul class="refund-evidence">
                    @foreach ($refundRequest->evidences as $evidence)
                        <li>
                            <span>{{ $evidence->original_name }} · {{ number_format($evidence->size / 1048576, 1) }} MB</span>
                            <a href="{{ route('admin.refund-evidence.download', $evidence) }}">Download</a>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($refundRequest->admin_note)
                <h3>Store response</h3>
                <p class="refund-description">{{ $refundRequest->admin_note }}</p>
            @endif
        </section>
    </div>

    <section class="panel refund-review-card">
        <h2>Review decision</h2>
        @if ($refundRequest->status === 'pending')
            <p class="refund-muted">Choose an outcome. Approval records an amount but does not transfer money.</p>
            <form method="POST" action="{{ route('admin.refunds.update', $refundRequest) }}" class="refund-review-form" id="refund-review-form">
                @csrf
                @method('PATCH')
                <div>
                    <label for="refund-status">Decision</label>
                    <select id="refund-status" name="status" required>
                        <option value="approved" @selected(old('status') === 'approved')>Approve request</option>
                        <option value="rejected" @selected(old('status') === 'rejected')>Decline request</option>
                    </select>
                </div>
                <div id="approved-amount-field">
                    <label for="approved-amount">Approved amount (₱)</label>
                    <input id="approved-amount" type="number" name="approved_amount" min="0.01" max="{{ number_format((float) $refundRequest->order->total, 2, '.', '') }}" step="0.01" value="{{ old('approved_amount', $refundRequest->order->total) }}">
                    @error('approved_amount') <p class="refund-field-error">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="admin-note">Response to customer <span id="admin-note-required">(required when declining)</span></label>
                    <textarea id="admin-note" name="admin_note" maxlength="2000">{{ old('admin_note') }}</textarea>
                    @error('admin_note') <p class="refund-field-error">{{ $message }}</p> @enderror
                </div>
                <button class="refund-primary-button" type="submit">Save decision</button>
            </form>
        @elseif ($refundRequest->status === 'approved')
            <p class="refund-muted">Confirm this refund has been sent to the customer outside this website before marking it complete.</p>
            <form method="POST" action="{{ route('admin.refunds.update', $refundRequest) }}">
                @csrf
                @method('PATCH')
                <input type="hidden" name="status" value="refunded">
                <button class="refund-primary-button" type="submit">Mark manually refunded</button>
            </form>
        @else
            <p class="refund-muted">
                @if ($refundRequest->status === 'refunded')
                    Completed {{ $refundRequest->completed_at?->timezone(config('app.timezone'))->format('F j, Y \a\t g:i A') }}. This website did not process the payment.
                @else
                    Reviewed {{ $refundRequest->reviewed_at?->timezone(config('app.timezone'))->format('F j, Y \a\t g:i A') }} by {{ $refundRequest->reviewer?->name ?? 'Store staff' }}.
                @endif
            </p>
        @endif
    </section>
@endsection

@push('styles')
    <style>
        .refund-heading { align-items: center; }
        .refund-heading h1 { margin-top: 7px; font-size: 20px; }
        .refund-heading p { margin: 5px 0 0; color: #888291; font-size: 10px; }
        .refund-back-link { color: #a995dc; font-size: 10px; text-decoration: none; }
        .refund-back-link:hover { color: #fff; }
        .refund-flash, .refund-error { margin-bottom: 12px; padding: 10px 12px; border: 1px solid rgba(48,180,122,.3); border-radius: 6px; color: #8de0b6; background: rgba(28,125,81,.1); font-size: 10px; }
        .refund-error { border-color: rgba(225,77,97,.3); color: #ff9aa7; background: rgba(151,34,52,.12); }
        .refund-error p { margin: 0; }
        .refund-detail-grid { display: grid; grid-template-columns: minmax(0, .9fr) minmax(0, 1.1fr); gap: 12px; }
        .refund-detail-card, .refund-review-card { min-width: 0; padding: 16px; }
        .refund-detail-card h2, .refund-review-card h2 { margin: 0 0 13px; font: 600 13px 'Outfit', sans-serif; }
        .refund-detail-card h3 { margin: 17px 0 8px; color: #c8c2d2; font-size: 10px; }
        .refund-facts { display: grid; gap: 9px; margin: 0; }
        .refund-facts div { display: flex; justify-content: space-between; gap: 12px; font-size: 10px; }
        .refund-facts dt { color: #898393; }
        .refund-facts dd { margin: 0; color: #eeeaf5; text-align: right; overflow-wrap: anywhere; }
        .refund-facts a { color: #c4b1ff; text-decoration: none; }
        .refund-items { display: grid; gap: 7px; margin: 0; padding-left: 17px; color: #c8c2d2; font-size: 10px; line-height: 1.5; }
        .refund-description { margin: 0; color: #d4cedd; font-size: 11px; line-height: 1.65; white-space: pre-wrap; overflow-wrap: anywhere; }
        .refund-muted { margin: 0 0 12px; color: #96909f; font-size: 10px; line-height: 1.55; }
        .refund-evidence { display: grid; gap: 8px; margin: 0; padding: 0; list-style: none; }
        .refund-evidence li { display: flex; align-items: center; justify-content: space-between; gap: 12px; color: #c8c2d2; font-size: 10px; overflow-wrap: anywhere; }
        .refund-evidence a { flex: 0 0 auto; color: #c4b1ff; text-decoration: none; }
        .refund-evidence a:hover { color: #fff; }
        .refund-review-card { margin-top: 12px; }
        .refund-review-form { display: grid; max-width: 560px; gap: 12px; }
        .refund-review-form label { display: block; margin-bottom: 5px; color: #c8c2d2; font-size: 10px; }
        .refund-review-form input, .refund-review-form select, .refund-review-form textarea { width: 100%; min-height: 34px; padding: 8px 10px; border: 1px solid #302a3b; border-radius: 5px; background: #13111a; color: #eee; font: inherit; font-size: 10px; }
        .refund-review-form textarea { min-height: 85px; resize: vertical; }
        .refund-review-form input:focus, .refund-review-form select:focus, .refund-review-form textarea:focus { border-color: #7045eb; outline: 0; }
        .refund-field-error { margin: 5px 0 0; color: #ff8e9b; font-size: 9px; }
        .refund-primary-button { min-height: 35px; justify-self: start; padding: 0 13px; border: 1px solid #5235a0; border-radius: 6px; background: linear-gradient(100deg, #5127d2, #7140e8); color: #fff; font: inherit; font-size: 10px; font-weight: 600; cursor: pointer; }
        .refund-primary-button:hover { filter: brightness(1.15); }
        @media (max-width: 760px) { .refund-detail-grid { grid-template-columns: 1fr; } }
        @media (max-width: 480px) { .refund-heading { align-items: flex-start; flex-direction: column; gap: 10px; } }
    </style>
@endpush

@push('scripts')
    <script>
        const refundStatus = document.getElementById('refund-status');
        if (refundStatus) {
            const amountField = document.getElementById('approved-amount');
            const noteField = document.getElementById('admin-note');
            const updateRefundFields = () => {
                amountField.required = refundStatus.value === 'approved';
                noteField.required = refundStatus.value === 'rejected';
            };

            refundStatus.addEventListener('change', updateRefundFields);
            updateRefundFields();
        }
    </script>
@endpush
