{{--
    SEO e GEO do casco (18/10/2026). Vale para toda página que estende o layout.

    - Endereço canônico sem query: `?demo=` da home e afins não viram páginas
      duplicadas para o buscador.
    - Open Graph e Twitter: o cartão que aparece quando o link é colado no
      WhatsApp, no LinkedIn ou num chat. A imagem da casa vem do painel (Configurações › Empresa; reserva `og-urbisdev.png`).
    - JSON-LD: quem é a casa (`Organization` + `ProfessionalService`), o que
      ela faz (`OfferCatalog`, as frentes da home) e onde se está no site
      (`BreadcrumbList`). É o que buscador e assistente de IA leem para
      responder "quem faz site com painel para imobiliária" sem adivinhar.
      Sem nome de produto e sem cliente (decisão de 18/10/2026), menos o USI,
      lançado e com endereço público (30/10/2026).

    A versão em texto corrido para assistentes de IA mora em `/llms.txt`
    (routes/web.php), e a lista de páginas em `/sitemap.xml`.
--}}
@php
    // `@section('x', 'texto')` guarda o texto já escapado; volta ao cru aqui
    // para o `{{ }}` e o JSON não escaparem duas vezes.
    $titulo = html_entity_decode(trim($__env->yieldContent('titulo', 'UrbisDev — Software que resolve problemas reais')), ENT_QUOTES);
    $descricao = html_entity_decode(trim($__env->yieldContent('descricao')), ENT_QUOTES);
    $canonico = url()->current();
    // Página com imagem própria (a capa de um texto do blog) troca a da casa na prévia.
    $imagemPropria = html_entity_decode(trim($__env->yieldContent('og_imagem')), ENT_QUOTES);
    $imagem = $imagemPropria ?: \App\Support\ConfiguracaoDoSite::imagemOg();
    $telefone = \App\Support\ConfiguracaoDoSite::telefone();
    $raiz = url('/');

    $casa = [
        '@type' => ['Organization', 'ProfessionalService'],
        '@id' => $raiz.'/#organizacao',
        'name' => 'UrbisDev',
        'url' => $raiz,
        'logo' => asset('favicon.svg'),
        'image' => $imagem,
        'email' => $site('contato.email'),
        'telephone' => $telefone,
        'description' => $site('empresa.descricao'),
        'areaServed' => ['@type' => 'Country', 'name' => 'Brasil'],
        'knowsLanguage' => 'pt-BR',
        'sameAs' => ['https://wa.me/'.$site('contato.whatsapp'), ...\App\Support\ConfiguracaoDoSite::perfis()],
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'contactType' => 'comercial',
            'email' => $site('contato.email'),
            'telephone' => $telefone,
            'availableLanguage' => 'Portuguese',
        ],
        'hasOfferCatalog' => [
            '@type' => 'OfferCatalog',
            'name' => 'O que fazemos',
            'itemListElement' => collect([
                ['USI: site e sistema de gestão para imobiliárias', 'Site com busca de imóveis, fichas de lançamento e blog, e o sistema de gestão do acervo e dos contatos, com a marca da imobiliária.', 'https://usi.urbisdev.tech'],
                ['Sites com painel próprio', 'Site para bandas, artistas, oficinas e profissionais, com painel para o próprio cliente trocar agenda, fotos, vídeos e textos.'],
                ['Sistema de gestão para clínicas de terapia', 'Agenda, evolução de cada paciente e cuidado com o dado de saúde desde o desenho. Em construção.'],
                ['Sistema para sindicatos e associações', 'Cadastro de associados, mensalidades, comunicação com a base e votações. Em construção.'],
            ])->map(fn ($s) => ['@type' => 'Offer', 'itemOffered' => array_filter(['@type' => 'Service', 'name' => $s[0], 'description' => $s[1], 'url' => $s[2] ?? null])])->all(),
        ],
    ];

    $grafo = [
        $casa,
        ['@type' => 'WebSite', '@id' => $raiz.'/#site', 'url' => $raiz, 'name' => 'UrbisDev', 'inLanguage' => 'pt-BR', 'publisher' => ['@id' => $raiz.'/#organizacao']],
        ['@type' => 'WebPage', '@id' => $canonico.'#pagina', 'url' => $canonico, 'name' => $titulo, 'description' => $descricao, 'inLanguage' => 'pt-BR', 'isPartOf' => ['@id' => $raiz.'/#site'], 'primaryImageOfPage' => $imagem],
    ];

    // Trilha só fora da home: "Início › <página>", com o nome antes do " — ".
    // Dentro do blog, o Blog entra no meio: "Início › Blog › <texto>" (06/11/2026).
    if (request()->path() !== '/') {
        $trilha = [['Início', $raiz]];
        if (request()->is('blog/*')) {
            $trilha[] = [config('blog.titulo'), url('/blog')];
        }
        $trilha[] = [\Illuminate\Support\Str::before($titulo, ' — '), $canonico];
        $grafo[] = ['@type' => 'BreadcrumbList', 'itemListElement' => collect($trilha)
            ->map(fn ($passo, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'name' => $passo[0], 'item' => $passo[1]])->all()];
    }

    // Auditoria SEO/GEO de 06/11/2026. Tag e mês do blog são listas de títulos
    // que repetem os textos: com poucos textos, é página rala aos olhos do
    // buscador. Ficam fora do índice (os links seguem valendo) e fora do sitemap
    // (`PaginasPublicas`). Quando o blog tiver volume, é só tirar daqui.
    $robots = request()->is('blog/tag/*', 'blog/secao/*') || preg_match('#^blog/\d{4}(/\d{2})?$#', request()->path())
        ? 'noindex, follow'
        : trim($__env->yieldContent('robots', 'index, follow, max-image-preview:large, max-snippet:-1'));
    // O texto do blog é artigo, não página: o cartão no LinkedIn e no WhatsApp e
    // o Open Graph dizem isso, com a data.
    $ehTexto = request()->routeIs(config('blog.nome_rota').'.show');

    // Montado AQUI, dentro do @php: escrito no `{!! !!}`, o Blade lia o
    // "@context" como a diretiva @context do Laravel 12 e quebrava o JSON.
    $jsonld = json_encode(['@context' => 'https://schema.org', '@graph' => $grafo], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG);
@endphp
    <meta name="robots" content="{{ $robots }}">
    <link rel="canonical" href="{{ $canonico }}">
    <meta name="theme-color" content="#0B1220">
    <meta property="og:locale" content="pt_BR">
    <meta property="og:type" content="{{ $ehTexto ? 'article' : 'website' }}">
    <meta property="og:site_name" content="UrbisDev">
    <meta property="og:title" content="{{ $titulo }}">
    <meta property="og:description" content="{{ $descricao }}">
    <meta property="og:url" content="{{ $canonico }}">
    <meta property="og:image" content="{{ $imagem }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    @unless ($imagemPropria)
    <meta property="og:image:alt" content="UrbisDev — sites, sistemas e aplicativos">
    @endunless
    <meta name="twitter:card" content="summary_large_image">
    <link rel="sitemap" type="application/xml" href="{{ url('/sitemap.xml') }}">
    <link rel="alternate" type="text/plain" title="Resumo para assistentes de IA" href="{{ url('/llms.txt') }}">
    <script type="application/ld+json">{!! $jsonld !!}</script>
