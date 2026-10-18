{{--
    SEO e GEO do casco (18/10/2026). Vale para toda página que estende o layout.

    - Endereço canônico sem query: `?demo=` da home e afins não viram páginas
      duplicadas para o buscador.
    - Open Graph e Twitter: o cartão que aparece quando o link é colado no
      WhatsApp, no LinkedIn ou num chat. A imagem é uma só (`og-urbisdev.png`).
    - JSON-LD: quem é a casa (`Organization` + `ProfessionalService`), o que
      ela faz (`OfferCatalog`, as frentes da home) e onde se está no site
      (`BreadcrumbList`). É o que buscador e assistente de IA leem para
      responder "quem faz site com painel para imobiliária" sem adivinhar.
      Sem nome de produto e sem cliente: decisão de 18/10/2026.

    A versão em texto corrido para assistentes de IA mora em `/llms.txt`
    (routes/web.php), e a lista de páginas em `/sitemap.xml`.
--}}
@php
    // `@section('x', 'texto')` guarda o texto já escapado; volta ao cru aqui
    // para o `{{ }}` e o JSON não escaparem duas vezes.
    $titulo = html_entity_decode(trim($__env->yieldContent('titulo', 'UrbisDev — Software que resolve problemas reais')), ENT_QUOTES);
    $descricao = html_entity_decode(trim($__env->yieldContent('descricao')), ENT_QUOTES);
    $canonico = url()->current();
    $imagem = asset('og-urbisdev.png');
    $raiz = url('/');

    $casa = [
        '@type' => ['Organization', 'ProfessionalService'],
        '@id' => $raiz.'/#organizacao',
        'name' => 'UrbisDev',
        'url' => $raiz,
        'logo' => asset('favicon.svg'),
        'image' => $imagem,
        'email' => 'contato@urbisdev.tech',
        'telephone' => '+55 11 97686-2551',
        'description' => 'Sites com painel próprio e sistemas de gestão para imobiliárias, clínicas, sindicatos, oficinas e artistas, do primeiro esboço ao app no celular.',
        'areaServed' => ['@type' => 'Country', 'name' => 'Brasil'],
        'knowsLanguage' => 'pt-BR',
        'sameAs' => ['https://wa.me/5511976862551'],
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'contactType' => 'comercial',
            'email' => 'contato@urbisdev.tech',
            'telephone' => '+55 11 97686-2551',
            'availableLanguage' => 'Portuguese',
        ],
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => 'O que fazemos',
            'itemListElement' => collect([
                ['Sites e sistema de gestão para imobiliárias', 'Site com busca de imóveis, fichas de lançamento e blog, e o sistema de gestão do acervo e dos contatos, com a marca da imobiliária.'],
                ['Sites com painel próprio', 'Site para bandas, artistas, oficinas e profissionais, com painel para o próprio cliente trocar agenda, fotos, vídeos e textos.'],
                ['Sistema de gestão para clínicas de terapia', 'Agenda, evolução de cada paciente e cuidado com o dado de saúde desde o desenho. Em construção.'],
                ['Sistema para sindicatos e associações', 'Cadastro de associados, mensalidades, comunicação com a base e votações. Em construção.'],
            ])->map(fn ($s) => ['@type' => 'Offer', 'itemOffered' => ['@type' => 'Service', 'name' => $s[0], 'description' => $s[1]]])->all(),
        ],
    ];

    $grafo = [
        $casa,
        ['@type' => 'WebSite', '@id' => $raiz.'/#site', 'url' => $raiz, 'name' => 'UrbisDev', 'inLanguage' => 'pt-BR', 'publisher' => ['@id' => $raiz.'/#organizacao']],
        ['@type' => 'WebPage', '@id' => $canonico.'#pagina', 'url' => $canonico, 'name' => $titulo, 'description' => $descricao, 'inLanguage' => 'pt-BR', 'isPartOf' => ['@id' => $raiz.'/#site'], 'primaryImageOfPage' => $imagem],
    ];

    // Trilha só fora da home: "Início › <página>", com o nome antes do " — ".
    if (request()->path() !== '/') {
        $grafo[] = ['@type' => 'BreadcrumbList', 'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Início', 'item' => $raiz],
            ['@type' => 'ListItem', 'position' => 2, 'name' => \Illuminate\Support\Str::before($titulo, ' — '), 'item' => $canonico],
        ]];
    }

    // Montado AQUI, dentro do @php: escrito no `{!! !!}`, o Blade lia o
    // "@context" como a diretiva @context do Laravel 12 e quebrava o JSON.
    $jsonld = json_encode(['@context' => 'https://schema.org', '@graph' => $grafo], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
@endphp
    <meta name="robots" content="@yield('robots', 'index, follow, max-image-preview:large, max-snippet:-1')">
    <link rel="canonical" href="{{ $canonico }}">
    <meta name="theme-color" content="#0B1220">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="UrbisDev">
    <meta property="og:title" content="{{ $titulo }}">
    <meta property="og:description" content="{{ $descricao }}">
    <meta property="og:url" content="{{ $canonico }}">
    <meta property="og:image" content="{{ $imagem }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:image:alt" content="UrbisDev — sites, sistemas e aplicativos">
    <meta name="twitter:card" content="summary_large_image">
    <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">
    <link rel="alternate" type="text/plain" title="Resumo para assistentes de IA" href="{{ url('/llms.txt') }}">
    <script type="application/ld+json">{!! $jsonld !!}</script>
