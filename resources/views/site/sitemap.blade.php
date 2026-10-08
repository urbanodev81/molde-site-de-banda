{!! '<?xml version="1.0" encoding="UTF-8"?>' !!}
@php $dominio = rtrim($config['seo.dominio'] ?? config('app.url'), '/'); @endphp
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ([['', '1.0', 'weekly'], ['/a-banda', '0.8', 'monthly'], ['/agenda', '0.9', 'weekly'], ['/galeria', '0.7', 'weekly'], ['/repertorio', '0.6', 'monthly'], ['/imprensa', '0.5', 'monthly'], ['/privacidade', '0.2', 'yearly']] as [$caminho, $peso, $frequencia])
    <url>
        <loc>{{ $dominio }}{{ $caminho }}</loc>
        @if ($atualizadoEm)<lastmod>{{ \Illuminate\Support\Carbon::parse($atualizadoEm)->toDateString() }}</lastmod>@endif
        <changefreq>{{ $frequencia }}</changefreq>
        <priority>{{ $peso }}</priority>
    </url>
@endforeach

@foreach ($abasDaGaleria as $aba)
    <url>
        <loc>{{ route('site.galeria.tipo', $aba) }}</loc>
        <changefreq>monthly</changefreq>
        <priority>0.5</priority>
    </url>
@endforeach

@foreach ($shows as $show)
    <url>
        <loc>{{ route('site.show', $show) }}</loc>
        <lastmod>{{ $show->updated_at->toDateString() }}</lastmod>
        <changefreq>{{ $show->jaAconteceu() ? 'yearly' : 'weekly' }}</changefreq>
        <priority>{{ $show->jaAconteceu() ? '0.4' : '0.7' }}</priority>
    </url>
@endforeach
</urlset>
