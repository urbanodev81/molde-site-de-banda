<ul class="creditos">
    @foreach ($integrantes as $pessoa)
        <li>
            @if ($pessoa->nomePublico())
                <b>{{ $pessoa->nomePublico() }}</b>
            @else
                <b>Nome <em class="marcador">a definir</em></b>
            @endif

            @if ($pessoa->instrumento)
                <span>{{ $pessoa->instrumento }}</span>
            @endif
        </li>
    @endforeach
</ul>
