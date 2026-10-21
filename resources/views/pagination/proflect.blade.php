@if ($paginator->hasPages())
    <nav class="proflect-pagination" role="navigation" aria-label="Pagination">
        <p>Showing <b>{{ $paginator->firstItem() }}</b> to <b>{{ $paginator->lastItem() }}</b> of <b>{{ $paginator->total() }}</b> results</p>
        <div class="proflect-pagination__links">
            @if ($paginator->onFirstPage())
                <span class="is-disabled" aria-disabled="true" aria-label="Previous page">‹</span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Previous page">‹</a>
            @endif

            @foreach ($elements as $element)
                @if (is_string($element))
                    <span class="proflect-pagination__ellipsis">{{ $element }}</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span class="is-current" aria-current="page">{{ $page }}</span>
                        @else
                            <a href="{{ $url }}" aria-label="Go to page {{ $page }}">{{ $page }}</a>
                        @endif
                    @endforeach
                @endif
            @endforeach

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Next page">›</a>
            @else
                <span class="is-disabled" aria-disabled="true" aria-label="Next page">›</span>
            @endif
        </div>
    </nav>
@endif
