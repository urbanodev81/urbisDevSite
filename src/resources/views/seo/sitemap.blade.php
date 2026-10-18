{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($paginas as $caminho)
    <url><loc>{{ url($caminho) }}</loc></url>
@endforeach
</urlset>
