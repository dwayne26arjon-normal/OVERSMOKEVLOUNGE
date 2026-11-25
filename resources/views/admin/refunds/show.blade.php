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

    @if ($errors->any())
        <div class="refund-error" role="alert">
            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    @if ($refundRequest->status === 'pending')
        <aside class="refund-review-checklist" data-refund-review-checklist aria-labelledby="refund-review-checklist-title">
            <span class="refund-review-checklist-icon" aria-hidden="true">!</span>
            <div>
                <h2 id="refund-review-checklist-title">Before deciding, check these items</h2>
                <ul>
                    <li>Compare the customer's description with the order and items.</li>
                    <li>Inspect the attached damage photos or videos.</li>
                    <li>Verify the e-wallet QR code if using it for the refund payment.</li>
                    <li>Keep the approved amount within the order total. Payment must be sent manually after approval.</li>
                </ul>
            </div>
        </aside>
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

            @php
                $damageEvidence = $refundRequest->evidences->where('purpose', '!=', 'payment_qr');
                $paymentQr = $refundRequest->evidences->firstWhere('purpose', 'payment_qr');
            @endphp
            <h3>Damage evidence ({{ $damageEvidence->count() }})</h3>
            @if ($damageEvidence->isEmpty())
                <p class="refund-muted">No files were attached.</p>
            @else
                <div class="refund-evidence-grid">
                    @foreach ($damageEvidence as $evidence)
                        <figure class="refund-evidence-card">
                            @if (str_starts_with($evidence->mime_type, 'image/'))
                                <img src="{{ route('admin.refund-evidence.preview', $evidence) }}" alt="Refund evidence photo {{ $loop->iteration }}" loading="lazy">
                                <figcaption>Photo {{ $loop->iteration }} · {{ number_format($evidence->size / 1048576, 1) }} MB</figcaption>
                            @elseif (str_starts_with($evidence->mime_type, 'video/'))
                                <video src="{{ route('admin.refund-evidence.preview', $evidence) }}" controls playsinline preload="metadata" aria-label="Refund evidence video {{ $loop->iteration }}"></video>
                                <figcaption>Video {{ $loop->iteration }} · {{ number_format($evidence->size / 1048576, 1) }} MB</figcaption>
                            @else
                                <figcaption>Preview unavailable · {{ number_format($evidence->size / 1048576, 1) }} MB</figcaption>
                            @endif
                            <a href="{{ route('admin.refund-evidence.download', $evidence) }}">Download file</a>
                        </figure>
                    @endforeach
                </div>
            @endif

            <h3>E-wallet payment QR</h3>
            @if ($paymentQr)
                <figure class="refund-evidence-card refund-payment-qr-card">
                    <img src="{{ route('admin.refund-evidence.preview', $paymentQr) }}" alt="Customer e-wallet payment QR code" loading="lazy">
                    <figcaption>{{ number_format($paymentQr->size / 1048576, 1) }} MB</figcaption>
                    <a href="{{ route('admin.refund-evidence.download', $paymentQr) }}">Download QR code</a>
                </figure>
            @else
                <p class="refund-muted">The customer did not provide an e-wallet QR code.</p>
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
            <form method="POST" action="{{ route('admin.refunds.update', $refundRequest) }}" class="refund-review-form" id="refund-review-form" data-refund-confirmation="review">
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
            <form method="POST" action="{{ route('admin.refunds.update', $refundRequest) }}" data-refund-confirmation="complete">
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

    <dialog class="refund-confirm-dialog" id="refundConfirmDialog" aria-labelledby="refundConfirmTitle" aria-describedby="refundConfirmMessage">
        <div class="refund-confirm-icon" aria-hidden="true">!</div>
        <p class="refund-confirm-eyebrow">Please confirm</p>
        <h2 id="refundConfirmTitle">Confirm refund decision?</h2>
        <p id="refundConfirmMessage">This will update the refund request and notify the customer.</p>
        <div class="refund-confirm-actions">
            <button class="refund-confirm-cancel" id="cancelRefundConfirmation" type="button">Go back</button>
            <button class="refund-confirm-proceed" id="confirmRefundAction" type="button">Confirm decision</button>
        </div>
    </dialog>
@endsection

@push('styles')
    <style>
        .refund-heading { align-items: center; }
        .refund-heading h1 { margin-top: 7px; font-size: 20px; }
        .refund-heading p { margin: 5px 0 0; color: #888291; font-size: 10px; }
        .refund-back-link { color: #a995dc; font-size: 10px; text-decoration: none; }
        .refund-back-link:hover { color: #fff; }
        .refund-status { display: inline-flex; min-height: 28px; align-items: center; justify-content: center; padding: 0 12px; border: 1px solid #312943; border-radius: 999px; background: #1c1725; color: #c4b1ff; font-size: 11px; font-weight: 600; }
        .refund-status.status-pending { border-color: rgba(212,160,45,.4); background: rgba(160,117,18,.14); color: #e8c35f; }
        .refund-status.status-approved, .refund-status.status-refunded { border-color: rgba(48,180,122,.35); background: rgba(28,125,81,.14); color: #60d9a1; }
        .refund-status.status-rejected { border-color: rgba(225,77,97,.4); background: rgba(151,34,52,.16); color: #ff7888; }
        .refund-error { margin-bottom: 12px; padding: 10px 12px; border: 1px solid rgba(225,77,97,.3); border-radius: 6px; color: #ff9aa7; background: rgba(151,34,52,.12); font-size: 10px; }
        .refund-error p { margin: 0; }
        .refund-review-checklist { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 12px; padding: 14px; border: 1px solid rgba(212,160,45,.38); border-left: 3px solid #d4a02d; border-radius: 7px; background: linear-gradient(110deg, rgba(160,117,18,.14), rgba(160,117,18,.045)); }
        .refund-review-checklist-icon { display: grid; width: 25px; height: 25px; flex: 0 0 25px; place-items: center; border: 1px solid rgba(232,195,95,.4); border-radius: 50%; color: #e8c35f; background: rgba(160,117,18,.18); font-size: 13px; font-weight: 700; }
        .refund-review-checklist h2 { margin: 0 0 7px; color: #f0d788; font: 600 12px 'Outfit', sans-serif; }
        .refund-review-checklist ul { display: grid; gap: 4px; margin: 0; padding-left: 16px; color: #c9c1ad; font-size: 10px; line-height: 1.5; }
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
        .refund-evidence-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px; }
        .refund-evidence-card { display: grid; min-width: 0; gap: 7px; margin: 0; padding: 8px; border: 1px solid #302a3b; border-radius: 6px; background: #13111a; }
        .refund-evidence-card img, .refund-evidence-card video { display: block; width: 100%; max-height: 260px; aspect-ratio: 4 / 3; border-radius: 4px; background: #09080d; object-fit: contain; }
        .refund-evidence-card figcaption { color: #a9a2b1; font-size: 9px; }
        .refund-evidence-card a { color: #c4b1ff; font-size: 9px; text-decoration: none; }
        .refund-evidence-card a:hover { color: #fff; }
        .refund-payment-qr-card { max-width: 280px; }
        .refund-review-card { margin-top: 12px; }
        .refund-review-form { display: grid; max-width: 560px; gap: 12px; }
        .refund-review-form label { display: block; margin-bottom: 5px; color: #c8c2d2; font-size: 10px; }
        .refund-review-form input, .refund-review-form select, .refund-review-form textarea { width: 100%; min-height: 34px; padding: 8px 10px; border: 1px solid #302a3b; border-radius: 5px; background: #13111a; color: #eee; font: inherit; font-size: 10px; }
        .refund-review-form textarea { min-height: 85px; resize: vertical; }
        .refund-review-form input:focus, .refund-review-form select:focus, .refund-review-form textarea:focus { border-color: #7045eb; outline: 0; }
        .refund-field-error { margin: 5px 0 0; color: #ff8e9b; font-size: 9px; }
        .refund-primary-button { min-height: 35px; justify-self: start; padding: 0 13px; border: 1px solid #5235a0; border-radius: 6px; background: linear-gradient(100deg, #5127d2, #7140e8); color: #fff; font: inherit; font-size: 10px; font-weight: 600; cursor: pointer; }
        .refund-primary-button:hover { filter: brightness(1.15); }
        .refund-confirm-dialog { width: min(420px, calc(100% - 32px)); padding: 24px; border: 1px solid #393044; border-radius: 12px; background: #17141f; color: #f4f1ff; box-shadow: 0 22px 70px rgba(0,0,0,.55); }
        .refund-confirm-dialog::backdrop { background: rgba(5,4,8,.72); backdrop-filter: blur(3px); }
        .refund-confirm-icon { display: grid; width: 38px; height: 38px; place-items: center; border: 1px solid rgba(212,160,45,.4); border-radius: 50%; background: rgba(160,117,18,.14); color: #e8c35f; font-size: 20px; font-weight: 700; }
        .refund-confirm-eyebrow { margin: 14px 0 5px; color: #b6a5ff; font-size: 9px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
        .refund-confirm-dialog h2 { margin: 0; font: 600 16px 'Outfit', sans-serif; }
        .refund-confirm-dialog > p:not(.refund-confirm-eyebrow) { margin: 8px 0 0; color: #aaa4b2; font-size: 11px; line-height: 1.55; }
        .refund-confirm-actions { display: flex; justify-content: flex-end; gap: 8px; margin-top: 21px; }
        .refund-confirm-actions button { min-height: 34px; padding: 0 12px; border: 1px solid #393347; border-radius: 6px; color: #d0cad8; background: #1b1823; font: inherit; font-size: 10px; font-weight: 600; cursor: pointer; }
        .refund-confirm-actions button:focus-visible { outline: 2px solid #a98cff; outline-offset: 2px; }
        .refund-confirm-actions .refund-confirm-proceed { border-color: #7655df; color: #fff; background: #6232e8; }
        .refund-confirm-actions .refund-confirm-proceed:hover { background: #7655f2; }
        .refund-confirm-actions .refund-confirm-proceed.is-danger { border-color: #9a3445; background: #8d293a; }
        .refund-confirm-actions .refund-confirm-proceed.is-danger:hover { background: #a83246; }
        @media (max-width: 760px) { .refund-detail-grid { grid-template-columns: 1fr; } }
        @media (max-width: 480px) { .refund-heading { align-items: flex-start; flex-direction: column; gap: 10px; } }
    </style>
@endpush

@push('scripts')
    <script>
        const refundStatus = document.getElementById('refund-status');
        const amountField = document.getElementById('approved-amount');
        if (refundStatus) {
            const noteField = document.getElementById('admin-note');
            const updateRefundFields = () => {
                amountField.required = refundStatus.value === 'approved';
                noteField.required = refundStatus.value === 'rejected';
            };

            refundStatus.addEventListener('change', updateRefundFields);
            updateRefundFields();
        }

        const refundConfirmDialog = document.getElementById('refundConfirmDialog');
        const refundConfirmTitle = document.getElementById('refundConfirmTitle');
        const refundConfirmMessage = document.getElementById('refundConfirmMessage');
        const confirmRefundAction = document.getElementById('confirmRefundAction');
        let pendingRefundForm = null;
        let confirmedRefundForm = null;

        document.querySelectorAll('[data-refund-confirmation]').forEach(form => {
            form.addEventListener('submit', event => {
                if (confirmedRefundForm === form) {
                    confirmedRefundForm = null;
                    return;
                }

                event.preventDefault();
                pendingRefundForm = form;
                confirmRefundAction.classList.remove('is-danger');

                if (form.dataset.refundConfirmation === 'complete') {
                    refundConfirmTitle.textContent = 'Mark refund as complete?';
                    refundConfirmMessage.textContent = 'Confirm you have sent the approved refund to the customer outside this website. This only records the completion; it does not transfer money.';
                    confirmRefundAction.textContent = 'Mark as refunded';
                } else if (refundStatus.value === 'approved') {
                    refundConfirmTitle.textContent = 'Approve this refund request?';
                    refundConfirmMessage.textContent = `Approve this refund request for ₱${amountField.value}? This records your decision for the customer to see in their order history. No payment will be sent by this website.`;
                    confirmRefundAction.textContent = 'Approve request';
                } else {
                    refundConfirmTitle.textContent = 'Decline this refund request?';
                    refundConfirmMessage.textContent = 'This will record your decision and notify the customer with your response.';
                    confirmRefundAction.textContent = 'Decline request';
                    confirmRefundAction.classList.add('is-danger');
                }

                refundConfirmDialog.showModal();
            });
        });

        document.getElementById('cancelRefundConfirmation').addEventListener('click', () => refundConfirmDialog.close());
        confirmRefundAction.addEventListener('click', () => {
            if (!pendingRefundForm) return;

            const form = pendingRefundForm;
            pendingRefundForm = null;
            confirmedRefundForm = form;
            refundConfirmDialog.close();
            form.requestSubmit();
            if (confirmedRefundForm === form) confirmedRefundForm = null;
        });
        refundConfirmDialog.addEventListener('close', () => {
            pendingRefundForm = null;
        });
        refundConfirmDialog.addEventListener('click', event => {
            if (event.target === refundConfirmDialog) refundConfirmDialog.close();
        });
    </script>
@endpush
