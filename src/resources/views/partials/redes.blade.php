{{--
    As quatro redes, num lugar só — 23/08/2026.

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
        // Instagram/LinkedIn visíveis desde 31/07; trocar o "#" pelas URLs
        // reais quando os perfis existirem — agora num lugar só.
        ['classe' => 's-ig', 'rotulo' => 'Instagram', 'href' => '#', 'externo' => false,
         'svg' => '<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>'],
        ['classe' => 's-li', 'rotulo' => 'LinkedIn', 'href' => '#', 'externo' => false,
         'svg' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>'],
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
