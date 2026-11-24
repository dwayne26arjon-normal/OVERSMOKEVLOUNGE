@if ($paginator->hasPages())
    <nav class="admin-pagination" role="navigation" aria-label="Pagination">
        @if ($paginator->onFirstPage())
            <span class="admin-pagination-control is-disabled" aria-disabled="true" aria-label="Previous page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </span>
        @else
            <a class="admin-pagination-control" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m15 18-6-6 6-6"/></svg>
            </a>
        @endif

        <div
            class="admin-pagination-window"
            data-pagination-window
            data-page-name="{{ $paginator->getPageName() }}"
            role="group"
            aria-label="Page numbers"
        >
            <div class="admin-pagination-track">
                <span class="admin-pagination-number is-current" aria-current="page" aria-label="Current page, {{ $paginator->currentPage() }}">{{ $paginator->currentPage() }}</span>
            </div>
        </div>

        @if ($paginator->hasMorePages())
            <a class="admin-pagination-control" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </a>
        @else
            <span class="admin-pagination-control is-disabled" aria-disabled="true" aria-label="Next page">
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="m9 18 6-6-6-6"/></svg>
            </span>
        @endif
    </nav>
@endif
