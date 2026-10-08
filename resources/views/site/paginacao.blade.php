@if ($paginator->hasPages())
    <ul class="paginacao__lista">
        @if ($paginator->onFirstPage())
            <li><span class="paginacao__item paginacao__item--inerte" aria-hidden="true">&#8249;</span></li>
        @else
            <li><a class="paginacao__item" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="Página anterior">&#8249;</a></li>
        @endif

        @foreach ($elements as $elemento)
            @if (is_string($elemento))
                <li><span class="paginacao__item paginacao__item--inerte">{{ $elemento }}</span></li>
            @endif

            @if (is_array($elemento))
                @foreach ($elemento as $pagina => $url)
                    @if ($pagina == $paginator->currentPage())
                        <li><span class="paginacao__item paginacao__item--atual" aria-current="page">{{ $pagina }}</span></li>
                    @else
                        <li><a class="paginacao__item" href="{{ $url }}" aria-label="Página {{ $pagina }}">{{ $pagina }}</a></li>
                    @endif
                @endforeach
            @endif
        @endforeach

        @if ($paginator->hasMorePages())
            <li><a class="paginacao__item" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="Próxima página">&#8250;</a></li>
        @else
            <li><span class="paginacao__item paginacao__item--inerte" aria-hidden="true">&#8250;</span></li>
        @endif
    </ul>
@endif
