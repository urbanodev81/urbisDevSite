@extends('layouts.site')

@section('titulo', 'Quanto a sua planilha custa por mês — calculadora UrbisDev')
@section('descricao', 'Calculadora: quantas pessoas mexem na planilha, quantas horas por semana e quanto custam os erros. Veja o custo do mês, do ano e quanto dá para recuperar com um sistema.')

@push('estilos')
@include('partials.demo-estilos')
<style>
    .calc-grade { display: grid; gap: 20px; align-items: start; }
    @media (min-width: 980px) { .calc-grade { grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr); } }
    .calc-grupo { margin-top: 20px; }
    .calc-grupo:first-of-type { margin-top: 0; }
    .calc-grupo > .rotulo { display: block; margin-bottom: 8px; font-size: .82rem; font-weight: 600; color: var(--fg-subtle); }
    .calc-campos { display: grid; gap: 12px; }
    @media (min-width: 560px) { .calc-campos { grid-template-columns: repeat(2, 1fr); } }
    .calc-campos label { display: grid; align-content: start; gap: 4px; font-size: .82rem; font-weight: 600; color: var(--fg-subtle); }
    .calc-campos input { min-height: 44px; padding: 0 12px; background: var(--bg); color: var(--fg); border: 1px solid var(--border-input); border-radius: var(--r-btn); font: inherit; font-size: 1rem; font-weight: 400; font-variant-numeric: tabular-nums; }
    .calc-campos small { font-weight: 400; color: var(--fg-muted); }
    .calc-hora { margin-top: 10px; font-size: .88rem; color: var(--fg-muted); }
    .calc-faixa { display: grid; gap: 6px; }
    .calc-faixa label { font-size: .82rem; font-weight: 600; color: var(--fg-subtle); }
    .calc-faixa input[type=range] { width: 100%; min-height: 44px; accent-color: var(--accent); }
    .calc-faixa output { font-family: var(--font-display); font-weight: 700; font-size: 1.15rem; color: var(--fg); font-variant-numeric: tabular-nums; }
    .calc-conta { margin-top: 16px; font-size: .88rem; line-height: 1.6; color: var(--fg-muted); }
    .calc-conta b { color: var(--fg); font-weight: 600; font-variant-numeric: tabular-nums; }
    .calc-aviso { margin-top: 14px; font-size: .85rem; color: var(--fg-muted); }
</style>
@endpush

@section('conteudo')
    @include('partials.demo-topo', [
        'introducao' => 'Planilha parece de graça, mas alguém passa horas copiando, colando, conferindo e consertando. Ponha os números da sua equipe e veja quanto isso custa no mês e no ano, e quanto um sistema que faz a parte repetitiva devolve.',
        'selo' => 'A conta usa só o que você digitar',
    ])

    <div class="container demo-corpo">
        <dl class="demo-numeros" aria-live="polite">
            <div class="demo-numero"><dt>Custo por mês</dt><dd id="calc-mes">—</dd><small>tempo da equipe + erros</small></div>
            <div class="demo-numero"><dt>Horas por mês</dt><dd id="calc-horas">—</dd><small id="calc-horas-d"></small></div>
            <div class="demo-numero"><dt>Em 12 meses</dt><dd id="calc-ano">—</dd><small>se nada mudar</small></div>
            <div class="demo-numero"><dt>Dá para recuperar por mês</dt><dd id="calc-volta">—</dd><small id="calc-volta-d"></small></div>
        </dl>

        <div class="calc-grade">
            <section class="demo-cartao" aria-labelledby="calc-dados-h">
                <h2 id="calc-dados-h">Os números da sua planilha</h2>
                <p class="sub">Não sabe exato? Chute por baixo: a conta fica conservadora.</p>

                <form id="calc-form" novalidate>
                    <div class="calc-grupo">
                        <span class="rotulo">Tempo</span>
                        <div class="calc-campos">
                            <label>Pessoas que mexem nela<input type="number" id="calc-pessoas" inputmode="numeric" min="1" max="200" step="1" value="2"></label>
                            <label>Horas por semana, cada uma<input type="number" id="calc-semana" inputmode="decimal" min="0" max="60" step="0.5" value="5"><small>copiar, colar, conferir, juntar abas, refazer</small></label>
                        </div>
                    </div>

                    <div class="calc-grupo">
                        <span class="rotulo">Quanto custa a hora</span>
                        <div class="calc-campos">
                            <label>Salário de quem mexe (R$ por mês)<input type="number" id="calc-salario" inputmode="decimal" min="0" step="100" value="3000"></label>
                            <label>Encargos e benefícios (%)<input type="number" id="calc-encargos" inputmode="numeric" min="0" max="200" step="5" value="70"><small>férias, 13º, FGTS, INSS, vale</small></label>
                        </div>
                        <p class="calc-hora" id="calc-hora" aria-live="polite"></p>
                    </div>

                    <div class="calc-grupo">
                        <span class="rotulo">Erros</span>
                        <div class="calc-campos">
                            <label>Erros por mês<input type="number" id="calc-erros" inputmode="numeric" min="0" max="500" step="1" value="2"><small>os que viram retrabalho ou prejuízo: fórmula quebrada, versão errada, dado digitado duas vezes</small></label>
                            <label>Custo médio de cada erro (R$)<input type="number" id="calc-custo-erro" inputmode="decimal" min="0" step="50" value="200"><small>desconto dado por engano, multa, venda perdida</small></label>
                        </div>
                    </div>

                    <div class="calc-grupo calc-faixa">
                        <label for="calc-automatiza">Quanto desse trabalho é repetitivo e um sistema faria sozinho</label>
                        <input type="range" id="calc-automatiza" min="0" max="100" step="5" value="60" aria-describedby="calc-automatiza-v">
                        <output id="calc-automatiza-v" for="calc-automatiza">60%</output>
                    </div>
                </form>
                <p class="calc-aviso">Nada é enviado nem guardado: a conta é feita no seu aparelho.</p>
            </section>

            <section class="demo-cartao" aria-labelledby="calc-onde-h">
                <h2 id="calc-onde-h">Para onde vai o dinheiro</h2>
                <p class="sub">Por mês, com os números ao lado.</p>
                <div class="ug" id="calc-barras"></div>
                <p class="calc-conta" id="calc-conta"></p>
            </section>
        </div>

        @include('partials.demo-chamada', ['chamada' => 'Olhamos a sua planilha de verdade e dizemos o que dá para automatizar, quanto custa fazer e em quantos meses se paga.'])
    </div>
@endsection

@push('scripts')
<script src="/demos/graficos.js?v={{ filemtime(public_path('demos/graficos.js')) }}"></script>
<script src="/demos/calculadora.js?v={{ filemtime(public_path('demos/calculadora.js')) }}"></script>
@endpush
