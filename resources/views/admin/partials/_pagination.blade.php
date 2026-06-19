@if ($paginator->hasPages())
    <div class="pagination">
        <a class="page-btn {{ $paginator->onFirstPage() ? 'disabled' : '' }}"
           href="{{ $paginator->onFirstPage() ? '#' : $paginator->previousPageUrl() }}">‹</a>

        @for ($p = 1; $p <= $paginator->lastPage(); $p++)
            <a class="page-btn {{ $p === $paginator->currentPage() ? 'active' : '' }}" href="{{ $paginator->url($p) }}">{{ $p }}</a>
        @endfor

        <a class="page-btn {{ $paginator->hasMorePages() ? '' : 'disabled' }}"
           href="{{ $paginator->hasMorePages() ? $paginator->nextPageUrl() : '#' }}">›</a>
    </div>
@endif
