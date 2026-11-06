# UrbisDev

> {{ $site('empresa.descricao') }} Empresa brasileira, atende todo o Brasil, em português.

Fazemos o código, o servidor e o acompanhamento depois de publicado. Todo projeto passa por testes automatizados, revisão de segurança, acessibilidade desde o desenho e cuidado com a LGPD.

@php
    // Site › Serviços (bloco 4b). Texto puro (text/plain + nosniff): `{!! !!}`
    // para o apóstrofo não virar &#039;.
    $servicos = \App\Support\ServicosDoSite::lista();
@endphp
@if ($servicos)
## O que fazemos

@foreach ($servicos as $s)
- {!! $s['titulo'] !!} ({{ mb_strtolower($s['situacao_rotulo']) }}): {!! $s['texto'] !!}@if ($s['link_url']) [{!! $s['link_rotulo'] !!}]({!! $s['link_url'] !!})@endif

@endforeach

@endif
## Contato

- E-mail: {{ $site('contato.email') }}
- WhatsApp: {{ \App\Support\ConfiguracaoDoSite::telefone() }}
- Formulário: {{ url('/#contato') }}

## Páginas

@foreach ($paginas as $caminho => $nome)
- [{{ \Illuminate\Support\Str::before($nome, ' — ') }}]({{ url($caminho) }}){{ str_contains($nome, ' — ') ? ': '.\Illuminate\Support\Str::after($nome, ' — ') : '' }}
@endforeach
