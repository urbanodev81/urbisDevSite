{{--
    As redes, num lugar só — 23/08/2026.

    A mesma lista aparecia três vezes (barra do rodapé da home, pilha
    flutuante da home, e o rodapé da outra página não tinha nenhuma). Trocar o
    "#" do Instagram pela URL real, quando o perfil existir, tinha que ser
    lembrado em cada cópia.

    Parâmetros: $classe (envoltório opcional), $tamanho (px do ícone).
--}}
@php
    $tamanho = $tamanho ?? 18;
    $redes = [
        ['classe' => 's-wa', 'rotulo' => 'WhatsApp', 'href' => 'https://wa.me/5511976862551', 'externo' => true,
         'svg' => '<path d="M3 21l1.65-4.95A8.96 8.96 0 0 1 3 11a9 9 0 1 1 9 9 8.96 8.96 0 0 1-5.05-1.65L3 21z"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/>'],
        // Instagram e LinkedIn saíram em 18/10/2026: estavam no ar desde 31/07
        // com href "#", sem perfil. Voltam com a URL real (classes `s-ig` e
        // `s-li`, que o layout ainda estiliza) e entram também no `sameAs` do
        // `partials/seo`. Os ícones estão no histórico do git deste arquivo.
        ['classe' => 's-mail', 'rotulo' => 'E-mail', 'href' => 'mailto:contato@urbisdev.tech', 'externo' => false,
         'svg' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>'],
    ];
@endphp
@if (! empty($classe))<div class="{{ $classe }}">@endif
    @foreach ($redes as $rede)
        <a class="{{ $rede['classe'] }}" href="{{ $rede['href'] }}" @if ($rede['externo']) target="_blank" rel="noopener" @endif aria-label="{{ $rede['rotulo'] }}">
            <svg width="{{ $tamanho }}" height="{{ $tamanho }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $rede['svg'] !!}</svg>
        </a>
    @endforeach
@if (! empty($classe))</div>@endif
