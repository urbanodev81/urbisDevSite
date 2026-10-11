@extends('layouts.site')

@section('titulo', 'Relatório automático — demonstração UrbisDev')
@section('descricao', 'Relatório mensal de exemplo, escrito sozinho a partir dos números: resumo em texto, gráfico, tabela e o arquivo em PDF. Dados inventados.')

@push('estilos')
@include('partials.demo-estilos')
<style>
    .rel-barra { display: flex; flex-wrap: wrap; align-items: end; justify-content: space-between; gap: 12px 20px; }
    .rel-folha { max-width: 860px; margin-inline: auto; padding: clamp(22px, 4vw, 48px); background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-card); box-shadow: var(--shadow-painel); }
    .rel-cabeca { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 6px 20px; padding-bottom: 14px; border-bottom: 2px solid var(--accent); }
    .rel-cabeca strong { font-family: var(--font-display); font-size: 1.15rem; color: var(--fg); }
    .rel-cabeca span { font-family: var(--font-mono); font-size: .74rem; letter-spacing: .1em; text-transform: uppercase; color: var(--fg-subtle); }
    .rel-folha h2 { margin: 22px 0 6px; font-size: clamp(1.5rem, 3.2vw, 2rem); }
    .rel-folha h3 { margin: 26px 0 8px; font-family: var(--font-body); font-size: 1rem; font-weight: 600; letter-spacing: 0; color: var(--fg); }
    .rel-texto p { margin-top: 10px; max-width: 68ch; line-height: 1.65; }
    .rel-texto b { color: var(--fg); font-weight: 600; }
    .rel-folha .demo-numeros { margin: 20px 0 4px; }
    .rel-folha .demo-numero { background: var(--bg); }
    .rel-rodape { margin-top: 28px; padding-top: 12px; border-top: 1px solid var(--border); font-size: .8rem; color: var(--fg-subtle); }

    /* O PDF é a própria folha: na impressão some o site e fica só ela. */
    @media print {
        @page { size: A4; margin: 14mm; }
        body > *:not(main), .demo-topo, .demo-filtros, .demo-chamada, .ug-tabela, .ug-dica { display: none !important; }
        html, body, main { background: #fff !important; }
        .container, .demo-corpo { max-width: none; padding: 0 !important; margin: 0 !important; }
        .rel-folha { max-width: none; padding: 0; border: 0; border-radius: 0; box-shadow: none; background: #fff; }
        .rel-folha h3, .demo-numeros, .demo-tabela-rolagem { break-inside: avoid; }
        .rel-folha h3 { break-after: avoid; }
    }
</style>
@endpush

@section('conteudo')
    @include('partials.demo-topo', ['introducao' => 'O relatório do mês, escrito sozinho a partir das vendas: o texto, os números e a tabela mudam quando você troca o mês. É o que chegaria no seu e-mail no dia 1º, sem ninguém montar.'])

    <div class="container demo-corpo">
        <div class="demo-filtros rel-barra">
            <div class="campo"><label for="re-mes">Mês do relatório</label><select id="re-mes"></select></div>
            <button type="button" class="btn btn-primary" id="re-pdf">Baixar em PDF</button>
        </div>

        <article class="rel-folha" aria-labelledby="re-titulo">
            <header class="rel-cabeca">
                <strong>Oficina Modelo</strong>
                <span>Relatório mensal · gerado automaticamente</span>
            </header>

            <h2 id="re-titulo"></h2>
            <div class="rel-texto" id="re-texto" aria-live="polite"></div>

            <dl class="demo-numeros">
                <div class="demo-numero"><dt>Faturamento</dt><dd id="re-fat"></dd><small id="re-fat-d"></small></div>
                <div class="demo-numero"><dt>Meta atingida</dt><dd id="re-meta"></dd><small id="re-meta-d"></small></div>
                <div class="demo-numero"><dt>Serviços vendidos</dt><dd id="re-qtd"></dd><small id="re-qtd-d"></small></div>
                <div class="demo-numero"><dt>Ticket médio</dt><dd id="re-ticket"></dd><small id="re-ticket-d"></small></div>
            </dl>

            <h3 id="re-evolucao-h">Os últimos seis meses</h3>
            <div id="re-evolucao"></div>

            <h3>Serviços do mês</h3>
            <div class="demo-tabela-rolagem">
                <table class="demo-tabela">
                    <thead><tr><th scope="col">Serviço</th><th scope="col">Vendas</th><th scope="col">Faturamento</th><th scope="col">Parte do mês</th></tr></thead>
                    <tbody id="re-servicos"></tbody>
                </table>
            </div>

            <h3>Equipe</h3>
            <div class="demo-tabela-rolagem">
                <table class="demo-tabela">
                    <thead><tr><th scope="col">Vendedor</th><th scope="col">Vendas</th><th scope="col">Faturamento</th><th scope="col">Ticket médio</th></tr></thead>
                    <tbody id="re-equipe"></tbody>
                </table>
            </div>

            <p class="rel-rodape">Oficina, pessoas e valores inventados para demonstração. Relatório montado pela UrbisDev.</p>
        </article>

        @include('partials.demo-chamada', ['chamada' => 'Montamos o relatório a partir do que você já tem, e ele chega pronto no dia combinado: por e-mail, em PDF ou no painel.'])
    </div>
@endsection

@push('scripts')
<script src="/demos/graficos.js?v={{ filemtime(public_path('demos/graficos.js')) }}"></script>
<script src="/demos/dados-oficina.js?v={{ filemtime(public_path('demos/dados-oficina.js')) }}"></script>
<script src="/demos/relatorio.js?v={{ filemtime(public_path('demos/relatorio.js')) }}"></script>
@endpush
