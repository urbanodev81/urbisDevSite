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
        ['classe' => 's-wa', 'rotulo' => 'WhatsApp', 'href' => \App\Support\ConfiguracaoDoSite::whatsappUrl(), 'externo' => true,
         'svg' => '<path d="M3 21l1.65-4.95A8.96 8.96 0 0 1 3 11a9 9 0 1 1 9 9 8.96 8.96 0 0 1-5.05-1.65L3 21z"/><path d="M9 10a.5.5 0 0 0 1 0V9a.5.5 0 0 0-1 0v1a5 5 0 0 0 5 5h1a.5.5 0 0 0 0-1h-1a.5.5 0 0 0 0 1"/>'],
        // Instagram e LinkedIn saíram em 18/10/2026 (apontavam para "#") e
        // voltam pelo painel (bloco 4a, 06/11/2026): só aparecem com o endereço
        // preenchido em Configurações › Redes, e entram também no `sameAs`.
        ['classe' => 's-ig', 'rotulo' => 'Instagram', 'href' => $site('redes.instagram'), 'externo' => true,
         'svg' => '<rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><line x1="17.5" y1="6.5" x2="17.51" y2="6.5"/>'],
        ['classe' => 's-li', 'rotulo' => 'LinkedIn', 'href' => $site('redes.linkedin'), 'externo' => true,
         'svg' => '<path d="M16 8a6 6 0 0 1 6 6v7h-4v-7a2 2 0 0 0-2-2 2 2 0 0 0-2 2v7h-4v-7a6 6 0 0 1 6-6z"/><rect x="2" y="9" width="4" height="12"/><circle cx="4" cy="4" r="2"/>'],
        // YouTube entrou em 20/11/2026 (canal @urbisDevTech, doc 43 do Sócrates).
        ['classe' => 's-yt', 'rotulo' => 'YouTube', 'href' => $site('redes.youtube'), 'externo' => true,
         'svg' => '<path d="M2.5 17a24.12 24.12 0 0 1 0-10 2 2 0 0 1 1.4-1.4 49.56 49.56 0 0 1 16.2 0A2 2 0 0 1 21.5 7a24.12 24.12 0 0 1 0 10 2 2 0 0 1-1.4 1.4 49.55 49.55 0 0 1-16.2 0A2 2 0 0 1 2.5 17"/><path d="m10 15 5-3-5-3z"/>'],
        // Facebook entrou em 20/11/2026 (página da Focus Media vira UrbisDev, doc 44 do Sócrates).
        ['classe' => 's-fb', 'rotulo' => 'Facebook', 'href' => $site('redes.facebook'), 'externo' => true,
         'svg' => '<path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/>'],
        ['classe' => 's-mail', 'rotulo' => 'E-mail', 'href' => 'mailto:'.$site('contato.email'), 'externo' => false,
         'svg' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>'],
    ];
@endphp
@if (! empty($classe))<div class="{{ $classe }}">@endif
    @foreach (array_filter($redes, fn ($r) => ! empty($r['href'])) as $rede)
        <a class="{{ $rede['classe'] }}" href="{{ $rede['href'] }}" @if ($rede['externo']) target="_blank" rel="noopener" @endif aria-label="{{ $rede['rotulo'] }}">
            <svg width="{{ $tamanho }}" height="{{ $tamanho }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $rede['svg'] !!}</svg>
        </a>
    @endforeach
@if (! empty($classe))</div>@endif
