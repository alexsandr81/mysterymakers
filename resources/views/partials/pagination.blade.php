@if ($paginator->hasPages())
<nav class="pagination" role="navigation">
    @if ($paginator->onFirstPage())
        <span class="pagination__item is-disabled">‹</span>
    @else
        <a class="pagination__item" href="{{ $paginator->previousPageUrl() }}" rel="prev">‹</a>
    @endif

    @foreach ($elements as $element)
        @if (is_string($element))
            <span class="pagination__item is-disabled">{{ $element }}</span>
        @endif
        @if (is_array($element))
            @foreach ($element as $page => $url)
                @if ($page == $paginator->currentPage())
                    <span class="pagination__item is-active">{{ $page }}</span>
                @else
                    <a class="pagination__item" href="{{ $url }}">{{ $page }}</a>
                @endif
            @endforeach
        @endif
    @endforeach

    @if ($paginator->hasMorePages())
        <a class="pagination__item" href="{{ $paginator->nextPageUrl() }}" rel="next">›</a>
    @else
        <span class="pagination__item is-disabled">›</span>
    @endif
</nav>
@endif
