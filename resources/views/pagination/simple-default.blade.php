@if ($paginator->hasPages())
    <nav role="navigation" aria-label="{{ __('Pagination Navigation') }}">
        <p class="pagination-summary">
            Hiển thị {{ $paginator->firstItem() }} đến {{ $paginator->lastItem() }} của {{ $paginator->total() }} kết quả
        </p>

        <div class="pagination-links">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" aria-label="{{ __('pagination.previous') }}">Trước</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('pagination.previous') }}">Trước</a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('pagination.next') }}">Sau</a>
            @else
                <span aria-disabled="true" aria-label="{{ __('pagination.next') }}">Sau</span>
            @endif
        </div>
    </nav>
@endif
