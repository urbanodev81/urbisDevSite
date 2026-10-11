@extends('layouts.site')

@section('titulo', 'Campeonato de futebol — demonstração UrbisDev')
@section('descricao', 'Painel de exemplo: classificação rodada a rodada, artilharia, comparador de times e mapa de finalizações. Dados inventados.')

@push('estilos')
@include('partials.demo-estilos')
<style>
    #fb-tabela .fb-pos { text-align: left; white-space: nowrap; }
    #fb-tabela .fb-pos b { display: inline-block; min-width: 1.6em; color: var(--fg); }
    #fb-tabela .fb-pos span { font-size: .7rem; }
    #fb-tabela .sobe { color: var(--ok); } #fb-tabela .desce { color: var(--error); } #fb-tabela .fica { color: var(--fg-subtle); }
    #fb-tabela .fb-pts { font-weight: 700; color: var(--fg); }
    #fb-tabela th:nth-child(2), #fb-tabela td:nth-child(2) { text-align: left; }
    /* O time escolhido ganha a marca da série dele na borda, não a linha inteira pintada. */
    #fb-tabela tr.fb-a th { box-shadow: inset 3px 0 0 var(--serie-1); }
    #fb-tabela tr.fb-b th { box-shadow: inset 3px 0 0 var(--serie-2); }
    #fb-comparador th:nth-child(2) i, #fb-comparador th:nth-child(3) i { display: inline-block; width: 10px; height: 10px; border-radius: 2px; margin-right: 6px; vertical-align: baseline; }
    .fb-gramado { width: 100%; height: auto; background: color-mix(in srgb, var(--serie-3) 14%, var(--surface)); border-radius: 10px; }
    .fb-linha { fill: none; stroke: var(--fg-subtle); stroke-width: .4; }
    .fb-chute { fill: var(--surface); stroke: var(--fg-muted); stroke-width: .5; }
    .fb-gol { fill: var(--serie-1); stroke: var(--surface); stroke-width: .5; }
    .fb-chave { display: flex; flex-wrap: wrap; gap: 6px 18px; margin-top: 10px; font-size: .85rem; list-style: none; }
    .fb-chave li { display: inline-flex; align-items: center; gap: 8px; }
    .fb-chave i { width: 12px; height: 12px; border-radius: 50%; flex: none; }
    .fb-chave .g { background: var(--serie-1); } .fb-chave .c { border: 1.5px solid var(--fg-muted); }
</style>
@endpush

@section('conteudo')
    @include('partials.demo-topo', ['introducao' => 'Dez times, dezoito rodadas. Arraste a rodada para ver a tabela se reordenar, e escolha dois times para comparar.'])

    <div class="container demo-corpo">
        <div class="demo-filtros">
            <div class="campo">
                <label for="fb-rodada">Rodada <span id="fb-rodada-n"></span></label>
                <input type="range" id="fb-rodada" min="1" max="18" step="1">
            </div>
            <div class="campo">
                <label for="fb-time-a">Time</label>
                <select id="fb-time-a"></select>
            </div>
            <div class="campo">
                <label for="fb-time-b">Comparar com</label>
                <select id="fb-time-b"></select>
            </div>
        </div>

        <dl class="demo-numeros">
            <div class="demo-numero"><dt>Líder</dt><dd id="fb-lider"></dd><small id="fb-lider-d"></small></div>
            <div class="demo-numero"><dt>Melhor ataque</dt><dd id="fb-ataque"></dd><small id="fb-ataque-d"></small></div>
            <div class="demo-numero"><dt>Gols por jogo</dt><dd id="fb-media"></dd><small id="fb-media-d"></small></div>
            <div class="demo-numero"><dt>Artilheiro</dt><dd id="fb-art"></dd><small id="fb-art-d"></small></div>
        </dl>

        <div class="demo-grade">
            <section class="demo-cartao larga" aria-labelledby="fb-h-tab">
                <h2 id="fb-h-tab">Classificação</h2>
                <p class="sub">A seta mostra quem subiu e quem caiu na rodada escolhida.</p>
                <div class="demo-tabela-rolagem">
                    <table class="demo-tabela" id="fb-tabela">
                        <thead><tr>
                            <th scope="col">Pos.</th><th scope="col">Time</th><th scope="col">Pontos</th><th scope="col">Jogos</th>
                            <th scope="col">Vitórias</th><th scope="col">Empates</th><th scope="col">Derrotas</th>
                            <th scope="col">Gols pró</th><th scope="col">Gols contra</th><th scope="col">Saldo</th>
                        </tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <section class="demo-cartao" aria-labelledby="fb-h-corrida">
                <h2 id="fb-h-corrida">A corrida dos dois times</h2>
                <p class="sub">Pontos acumulados, rodada a rodada.</p>
                <div id="fb-corrida"></div>
            </section>

            <section class="demo-cartao" aria-labelledby="fb-h-comp">
                <h2 id="fb-h-comp">Lado a lado</h2>
                <p class="sub">Os números dos dois times até a rodada escolhida.</p>
                <div class="demo-tabela-rolagem">
                    <table class="demo-tabela" id="fb-comparador">
                        <thead><tr>
                            <th scope="col"><span class="sr-only" style="position:absolute;left:-9999px">Medida</span></th>
                            <th scope="col"><i style="background:var(--serie-1)"></i><span id="fb-comp-a"></span></th>
                            <th scope="col"><i style="background:var(--serie-2)"></i><span id="fb-comp-b"></span></th>
                        </tr></thead>
                        <tbody></tbody>
                    </table>
                </div>
            </section>

            <section class="demo-cartao" aria-labelledby="fb-h-art">
                <h2 id="fb-h-art">Artilharia</h2>
                <p class="sub">Os oito que mais marcaram.</p>
                <div id="fb-artilharia"></div>
            </section>

            <section class="demo-cartao" aria-labelledby="fb-h-campo">
                <h2 id="fb-h-campo">De onde o time finaliza</h2>
                <p class="sub" id="fb-campo-resumo"></p>
                <div id="fb-campo"></div>
                <ul class="fb-chave"><li><i class="g"></i>Gol</li><li><i class="c"></i>Finalização sem gol</li></ul>
            </section>
        </div>

        @include('partials.demo-chamada')
    </div>
@endsection

@push('scripts')
<script src="/demos/graficos.js?v={{ filemtime(public_path('demos/graficos.js')) }}"></script>
<script src="/demos/futebol.js?v={{ filemtime(public_path('demos/futebol.js')) }}"></script>
@endpush
