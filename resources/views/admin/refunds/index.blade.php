@extends('admin.layout')

@section('title', 'Refund requests')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Refund requests</h1>
            <p>Review damage reports, evidence, and manually processed refunds.</p>
        </div>
    </div>

    <section class="refund-summary" aria-label="Refund request counts">
        @foreach (['pending' => 'Needs review', 'approved' => 'Approved', 'rejected' => 'Declined', 'refunded' => 'Completed'] as $key => $label)
            <a class="refund-summary-card {{ request('status') === $key ? 'active' : '' }}" href="{{ route('admin.refunds.index', array_merge(request()->only(['date', 'q']), ['status' => $key])) }}">
                <span>{{ $label }}</span>
                <strong>{{ $counts->get($key, 0) }}</strong>
            </a>
        @endforeach
        <a class="refund-summary-card {{ request()->filled('status') ? '' : 'active' }}" href="{{ route('admin.refunds.index', request()->only(['date', 'q'])) }}">
            <span>All requests</span>
            <strong>{{ $counts->sum() }}</strong>
        </a>
    </section>

    <section class="panel refund-panel">
        <form class="refund-filter-form" method="GET" action="{{ route('admin.refunds.index') }}">
            @if (request()->filled('q'))
                <input type="hidden" name="q" value="{{ request('q') }}">
            @endif
            <div class="refund-filter-field">
                <label for="refund-request-date">Request date</label>
                <input id="refund-request-date" type="date" name="date" value="{{ request('date') }}">
            </div>
            <div class="refund-filter-field">
                <label for="refund-request-status">Status</label>
                <select id="refund-request-status" name="status">
                    <option value="">All requests</option>
                    <option value="pending" @selected(request('status') === 'pending')>Needs review</option>
                    <option value="approved" @selected(request('status') === 'approved')>Approved</option>
                    <option value="rejected" @selected(request('status') === 'rejected')>Declined</option>
                    <option value="refunded" @selected(request('status') === 'refunded')>Completed</option>
                </select>
            </div>
            <button class="refund-filter-button" type="submit">Apply filters</button>
            @if (request()->filled('date') || request()->filled('status'))
                <a class="refund-filter-reset" href="{{ route('admin.refunds.index', request()->only('q')) }}">Clear filters</a>
            @endif
        </form>

        @if ($requests->isEmpty())
            <div class="empty-state">
                {{ request()->filled('status') || request()->filled('date') || request()->filled('q') ? 'No refund requests match these filters.' : 'No refund requests yet.' }}
            </div>
        @else
            <div class="table-wrap">
                <table class="data-table refund-table">
                    <thead>
                        <tr><th>ORDER</th><th>CUSTOMER</th><th>SUBMITTED</th><th>AMOUNT</th><th>STATUS</th><th></th></tr>
                    </thead>
                    <tbody>
                    @foreach ($requests as $refundRequest)
                        <tr>
                            <td><a class="refund-order-link" href="{{ route('admin.orders.show', $refundRequest->order) }}">{{ $refundRequest->order->order_number }}</a></td>
                            <td>
                                <span class="refund-customer-name">{{ $refundRequest->customer->name }}</span>
                                <span class="refund-customer-email">{{ $refundRequest->customer->email }}</span>
                            </td>
                            <td>{{ $refundRequest->created_at->timezone(config('app.timezone'))->format('M j, Y · g:i A') }}</td>
                            <td>{{ $refundRequest->approved_amount ? '₱'.number_format((float) $refundRequest->approved_amount, 2) : 'Pending decision' }}</td>
                            <td><span class="refund-status status-{{ $refundRequest->status }}">{{ ucfirst($refundRequest->status) }}</span></td>
                            <td><a class="table-action" href="{{ route('admin.refunds.show', $refundRequest) }}">Review</a></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            <div class="pagination pagination-window">
                <span>Showing {{ $requests->firstItem() }}-{{ $requests->lastItem() }} of {{ $requests->total() }} requests</span>
                @if ($requests->hasPages())
                    {{ $requests->links('admin.pagination.scroll') }}
                @endif
            </div>
        @endif
    </section>
@endsection

@push('styles')
    <style>
        .refund-summary { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 9px; margin-bottom: 14px; }
        .refund-summary-card { display: flex; min-height: 65px; flex-direction: column; justify-content: center; gap: 5px; padding: 10px 12px; border: 1px solid #2b2634; border-radius: 7px; background: #17141f; color: #a9a2b1; font-size: 9px; text-decoration: none; }
        .refund-summary-card strong { color: #f4f1ff; font: 600 18px 'Outfit', sans-serif; }
        .refund-summary-card:hover, .refund-summary-card.active { border-color: #6543bb; background: #211a34; color: #d7caff; }
        .refund-panel { overflow: hidden; }
        .refund-filter-form { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 10px; padding: 14px; border-bottom: 1px solid #2b2634; }
        .refund-filter-field { display: grid; min-width: 170px; gap: 5px; }
        .refund-filter-field label { color: #a9a2b1; font-size: 9px; }
        .refund-filter-field input, .refund-filter-field select { min-height: 34px; padding: 0 9px; border: 1px solid #302a3b; border-radius: 5px; color: #eeeaf5; background: #13111a; font: inherit; font-size: 10px; }
        .refund-filter-button { min-height: 34px; padding: 0 12px; border: 1px solid #5235a0; border-radius: 5px; color: #fff; background: #5127d2; font: inherit; font-size: 10px; font-weight: 600; cursor: pointer; }
        .refund-filter-button:hover { filter: brightness(1.15); }
        .refund-filter-reset { min-height: 34px; display: inline-flex; align-items: center; padding: 0 4px; color: #c4b1ff; font-size: 10px; text-decoration: none; }
        .refund-filter-reset:hover { color: #fff; }
        .refund-table { min-width: 680px; }
        .refund-order-link { color: #c4b1ff; font-weight: 600; text-decoration: none; }
        .refund-order-link:hover { color: #fff; }
        .refund-customer-name, .refund-customer-email { display: block; }
        .refund-customer-name { color: #eeeaf5; font-weight: 600; }
        .refund-customer-email { margin-top: 3px; color: #797382; font-size: 9px; }
        .refund-status { display: inline-flex; min-height: 19px; align-items: center; padding: 0 7px; border: 1px solid #312943; border-radius: 4px; background: #1c1725; color: #c4b1ff; font-size: 9px; }
        .refund-status.status-pending { border-color: rgba(212,160,45,.28); background: rgba(160,117,18,.1); color: #e8c35f; }
        .refund-status.status-approved, .refund-status.status-refunded { border-color: rgba(48,180,122,.24); background: rgba(28,125,81,.1); color: #60d9a1; }
        .refund-status.status-rejected { border-color: rgba(225,77,97,.27); background: rgba(151,34,52,.12); color: #ff7888; }
        @media (max-width: 760px) { .refund-summary { grid-template-columns: repeat(3, minmax(0, 1fr)); } }
        @media (max-width: 480px) { .refund-summary { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    </style>
@endpush
