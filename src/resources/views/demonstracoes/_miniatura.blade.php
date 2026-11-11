{{--
    A miniatura viva de cada cartão da vitrine (10/11/2026, "mais vida nas
    demonstrações"). Um desenho pequeno, em SVG, do que tem dentro da
    demonstração, em laço lento. Decorativo: `aria-hidden`, o texto do cartão
    é quem diz o que é.

    Regras do movimento (CSS em `demonstracoes/index.blade.php`):
    - `.a` = movimento que DESLOCA (cresce, troca de lugar, anda). Com menos
      movimento ele TROCA por um pulso de luz no mesmo ritmo (nunca desliga).
    - `.c` = só luz ou cor (acende, troca de cor). Fica igual com menos
      movimento: não desloca nada.
    - O desenho parado (sem animação nenhuma) é o estado completo, então
      "Pausar animações" deixa a miniatura inteira à vista.
    - Cor só por token.

    Slug sem desenho aqui não ganha miniatura (o cartão fica como era).
--}}
@php($vb = 'viewBox="0 0 320 120" preserveAspectRatio="xMidYMid meet"')
@switch($slug)
    @case('futebol')
        {{-- Rodada: os Tubarões fazem 3 pontos (27 → 30) e passam os Gaviões. A posição fica parada; quem anda é o time. --}}
        <svg {!! $vb !!} class="mini-svg" aria-hidden="true" focusable="false">
            @foreach ([['Leões', 'var(--serie-1)', 31], ['Gaviões', 'var(--serie-2)', 28], ['Tubarões', 'var(--serie-3)', 27], ['Falcões', 'var(--serie-4)', 22]] as $i => [$time, $cor, $pts])
                <rect x="40" y="{{ 12 + $i * 25 }}" width="240" height="21" rx="5" class="mini-linha"/>
                <text x="52" y="{{ 27 + $i * 25 }}" class="mini-txt">{{ $i + 1 }}º</text>
            @endforeach
            @foreach ([['Leões', 'var(--serie-1)', 31], ['Gaviões', 'var(--serie-2)', 28], ['Tubarões', 'var(--serie-3)', 27], ['Falcões', 'var(--serie-4)', 22]] as $i => [$time, $cor, $pts])
                <g class="{{ $i === 1 ? 'a mini-desce' : ($i === 2 ? 'a mini-sobe' : '') }}">
                    @if ($i === 2)
                        <rect x="72" y="{{ 13 + $i * 25 }}" width="207" height="19" rx="4" class="c mini-destaque"/>
                    @endif
                    <circle cx="82" cy="{{ 22.5 + $i * 25 }}" r="6" style="fill: {{ $cor }}"/>
                    <text x="96" y="{{ 27 + $i * 25 }}" class="mini-txt forte">{{ $time }}</text>
                    @if ($i === 2)
                        <text x="266" y="{{ 27 + $i * 25 }}" text-anchor="end" class="mini-txt forte c mini-antes">27</text>
                        <text x="266" y="{{ 27 + $i * 25 }}" text-anchor="end" class="mini-txt forte c mini-depois">30</text>
                    @else
                        <text x="266" y="{{ 27 + $i * 25 }}" text-anchor="end" class="mini-txt forte">{{ $pts }}</text>
                    @endif
                </g>
            @endforeach
        </svg>
        @break

    @case('comercial')
        <svg {!! $vb !!} class="mini-svg" aria-hidden="true" focusable="false">
            @foreach ([46, 60, 52, 74, 66, 88] as $i => $h)
                <rect x="{{ 52 + $i * 38 }}" y="{{ 104 - $h }}" width="22" height="{{ $h }}" rx="3" class="a mini-cresce" style="--d: {{ $i * .12 }}s; fill: {{ $i === 5 ? 'var(--accent)' : 'var(--serie-1)' }}"/>
            @endforeach
            <line x1="40" x2="290" y1="34" y2="34" class="c mini-meta"/>
            <text x="40" y="27" class="mini-txt">meta</text>
            <line x1="40" x2="290" y1="104.5" y2="104.5" class="mini-base"/>
        </svg>
        @break

    @case('imobiliaria')
        <svg {!! $vb !!} class="mini-svg" aria-hidden="true" focusable="false">
            @foreach ([[200, 'Leads'], [150, 'Responderam'], [96, 'Visitaram'], [48, 'Fecharam']] as $i => [$w, $rot])
                <rect x="{{ 160 - $w / 2 }}" y="{{ 12 + $i * 25 }}" width="{{ $w }}" height="19" rx="4" class="a mini-abre" style="--d: {{ $i * .25 }}s; fill: var(--serie-{{ $i + 1 }})"/>
            @endforeach
            @foreach ([0, 1, 2] as $k)
                <circle cx="{{ 150 + $k * 10 }}" cy="6" r="3.2" class="a mini-cai" style="--d: {{ $k * 1.1 }}s"/>
            @endforeach
        </svg>
        @break

    @case('atendimento')
        <svg {!! $vb !!} class="mini-svg" aria-hidden="true" focusable="false">
            @foreach ([[150, 'var(--serie-1)'], [110, 'var(--serie-2)'], [70, 'var(--serie-3)']] as $i => [$w, $cor])
                <rect x="30" y="{{ 24 + $i * 28 }}" width="170" height="14" rx="7" class="mini-trilho"/>
                <rect x="30" y="{{ 24 + $i * 28 }}" width="{{ $w }}" height="14" rx="7" class="a mini-enche" style="--d: {{ $i * .3 }}s; fill: {{ $cor }}"/>
            @endforeach
            <circle cx="262" cy="60" r="32" class="mini-trilho-anel"/>
            <circle cx="262" cy="60" r="32" pathLength="100" class="a mini-anel" transform="rotate(-90 262 60)"/>
            <text x="262" y="66" text-anchor="middle" class="mini-txt forte grande">92%</text>
        </svg>
        @break

    @case('sites')
        <svg {!! $vb !!} class="mini-svg" aria-hidden="true" focusable="false">
            <rect x="60" y="8" width="200" height="104" rx="8" class="mini-janela"/>
            <circle cx="72" cy="18" r="2.6" class="mini-pingo"/><circle cx="81" cy="18" r="2.6" class="mini-pingo"/><circle cx="90" cy="18" r="2.6" class="mini-pingo"/>
            <rect x="60" y="27" width="200" height="34" class="c mini-veste"/>
            <rect x="72" y="36" width="16" height="16" rx="4" class="mini-logo"/>
            <rect x="96" y="40" width="70" height="8" rx="4" class="mini-logo"/>
            <rect x="72" y="72" width="120" height="7" rx="3.5" class="mini-texto"/>
            <rect x="72" y="85" width="90" height="7" rx="3.5" class="mini-texto"/>
            <rect x="196" y="78" width="52" height="20" rx="6" class="c mini-veste"/>
        </svg>
        @break

    @case('agendamento')
        <svg {!! $vb !!} class="mini-svg" aria-hidden="true" focusable="false">
            @foreach (range(0, 2) as $l)
                @foreach (range(0, 5) as $col)
                    @php($ocupado = in_array([$l, $col], [[0, 1], [1, 4], [2, 0], [2, 3]]))
                    <rect x="{{ 46 + $col * 39 }}" y="{{ 14 + $l * 32 }}" width="33" height="26" rx="5" class="{{ $ocupado ? 'mini-ocupado' : 'mini-livre' }}"/>
                @endforeach
            @endforeach
            @foreach ([[0, 3, 0], [1, 1, 2], [2, 5, 4]] as [$l, $col, $d])
                <g class="c mini-marca" style="--d: {{ $d }}s">
                    <rect x="{{ 46 + $col * 39 }}" y="{{ 14 + $l * 32 }}" width="33" height="26" rx="5" class="mini-escolhido"/>
                    <path d="M{{ 55 + $col * 39 }} {{ 27 + $l * 32 }}l5 5l9 -10" class="mini-ok"/>
                </g>
            @endforeach
        </svg>
        @break

    @case('relatorio')
        <svg {!! $vb !!} class="mini-svg" aria-hidden="true" focusable="false">
            <rect x="80" y="6" width="160" height="108" rx="6" class="mini-janela"/>
            <rect x="94" y="16" width="70" height="8" rx="4" class="mini-titulo"/>
            <line x1="94" x2="226" y1="30" y2="30" class="mini-regua"/>
            @foreach ([118, 132, 100] as $i => $w)
                <rect x="94" y="{{ 38 + $i * 11 }}" width="{{ $w }}" height="5" rx="2.5" class="a mini-escreve" style="--d: {{ $i * .5 }}s"/>
            @endforeach
            @foreach ([22, 30, 26, 36] as $i => $h)
                <rect x="{{ 100 + $i * 30 }}" y="{{ 104 - $h }}" width="16" height="{{ $h }}" rx="2" class="a mini-cresce" style="--d: {{ 1.4 + $i * .12 }}s; fill: var(--serie-1)"/>
            @endforeach
        </svg>
        @break

    @case('lancamento')
        <svg {!! $vb !!} class="mini-svg" aria-hidden="true" focusable="false">
            <rect x="112" y="10" width="96" height="104" rx="3" class="mini-predio"/>
            <rect x="62" y="48" width="44" height="66" rx="3" class="mini-predio baixo"/>
            <rect x="214" y="38" width="44" height="76" rx="3" class="mini-predio baixo"/>
            @foreach (range(0, 5) as $l)
                @foreach (range(0, 3) as $col)
                    <rect x="{{ 121 + $col * 21 }}" y="{{ 20 + $l * 15 }}" width="12" height="8" rx="1.5" class="c mini-janelinha" style="--d: {{ (($l * 7 + $col * 5) % 11) * .45 }}s"/>
                @endforeach
            @endforeach
            <line x1="40" x2="280" y1="114.5" y2="114.5" class="mini-base"/>
        </svg>
        @break

    @case('calculadora')
        <svg {!! $vb !!} class="mini-svg" aria-hidden="true" focusable="false">
            <text x="40" y="30" class="mini-txt">Horas por semana na planilha</text>
            <rect x="40" y="42" width="240" height="6" rx="3" class="mini-trilho"/>
            <circle cx="70" cy="45" r="9" class="a mini-cursor"/>
            <text x="40" y="78" class="mini-txt">Custo por mês</text>
            <rect x="40" y="88" width="240" height="18" rx="5" class="mini-trilho"/>
            <rect x="40" y="88" width="240" height="18" rx="5" class="a mini-custo"/>
        </svg>
        @break

    @case('planilha')
        <svg {!! $vb !!} class="mini-svg" aria-hidden="true" focusable="false">
            @foreach (range(0, 3) as $l)
                @foreach (range(0, 3) as $col)
                    <rect x="{{ 34 + $col * 27 }}" y="{{ 18 + $l * 22 }}" width="25" height="20" class="c mini-celula" style="--d: {{ ($l * 4 + $col) * .08 }}s"/>
                @endforeach
            @endforeach
            <path d="M160 60h26m-8 -7l8 7l-8 7" class="mini-seta"/>
            @foreach ([38, 64, 50, 80] as $i => $h)
                <rect x="{{ 206 + $i * 22 }}" y="{{ 104 - $h }}" width="15" height="{{ $h }}" rx="2" class="a mini-cresce" style="--d: {{ 1.4 + $i * .12 }}s; fill: var(--serie-{{ $i + 1 }})"/>
            @endforeach
        </svg>
        @break

    @case('pergunte')
        <svg {!! $vb !!} class="mini-svg" aria-hidden="true" focusable="false">
            <rect x="150" y="12" width="130" height="26" rx="13" class="c mini-balao eu"/>
            <g class="c mini-digitando">
                @foreach ([0, 1, 2] as $k)
                    <circle cx="{{ 56 + $k * 11 }}" cy="62" r="3.5" class="a mini-ponto" style="--d: {{ $k * .15 }}s"/>
                @endforeach
            </g>
            <g class="c mini-resposta">
                <rect x="40" y="50" width="170" height="56" rx="14" class="mini-balao"/>
                <rect x="54" y="63" width="120" height="6" rx="3" class="mini-texto"/>
                <rect x="54" y="75" width="140" height="6" rx="3" class="mini-texto"/>
                <rect x="54" y="87" width="90" height="6" rx="3" class="mini-texto"/>
            </g>
        </svg>
        @break
@endswitch
