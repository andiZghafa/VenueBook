@if ($paginator->hasPages())
    <div class="pagination">
        @if ($paginator->onFirstPage())
            <span>&laquo; Sebelumnya</span>
        @else
            <a href="{{ $paginator->previousPageUrl() }}">&laquo; Sebelumnya</a>
        @endif

        @foreach ($paginator->getUrlRange(1, $paginator->lastPage()) as $page => $url)
            <a href="{{ $url }}" class="{{ $page === $paginator->currentPage() ? 'active' : '' }}">{{ $page }}</a>
        @endforeach

        @if ($paginator->hasMorePages())
            <a href="{{ $paginator->nextPageUrl() }}">Berikutnya &raquo;</a>
        @else
            <span>Berikutnya &raquo;</span>
        @endif
    </div>
@endif
