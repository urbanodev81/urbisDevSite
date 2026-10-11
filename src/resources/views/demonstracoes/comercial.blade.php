@extends('layouts.site')

@section('titulo', 'Comercial de uma oficina — demonstração UrbisDev')
@section('descricao', 'Painel de exemplo: faturamento contra a meta, ticket médio, serviços mais vendidos e dias mais fortes. Dados inventados.')

@push('estilos')
@include('partials.demo-estilos')

@endpush

@section('conteudo')
    @include('partials.demo-topo', ['introducao' => 'Um ano de vendas de uma oficina. Troque o período e o vendedor: os números e os gráficos acompanham.'])

    <div class="container demo-corpo">
        <div class="demo-filtros">
            <div class="campo"><span class="rotulo" id="co-periodo-r">Período</span><div class="demo-botoes" id="co-periodo" role="group" aria-labelledby="co-periodo-r"><button type="button" data-valor="3" aria-pressed="false">3 meses</button><button type="button" data-valor="6" aria-pressed="false">6 meses</button><button type="button" data-valor="12" aria-pressed="true">12 meses</button></div></div>
            <div class="campo"><label for="co-vendedor">Vendedor</label><select id="co-vendedor"><option value="todos">Toda a equipe</option></select></div>
        </div>

        <dl class="demo-numeros">
            <div class="demo-numero"><dt>Faturamento</dt><dd id="co-fat"></dd><small id="co-fat-d"></small></div>
            <div class="demo-numero"><dt>Ticket médio</dt><dd id="co-ticket"></dd><small id="co-ticket-d"></small></div>
            <div class="demo-numero"><dt>Serviços vendidos</dt><dd id="co-qtd"></dd><small id="co-qtd-d"></small></div>
            <div class="demo-numero"><dt>Meta atingida</dt><dd id="co-meta"></dd><small id="co-meta-d"></small></div>
        </dl>

        <div class="demo-grade">
            <section class="demo-cartao larga" aria-labelledby="co-mensal-h"><h2 id="co-mensal-h">Faturamento por mês</h2><p class="sub">A linha tracejada é a meta de cada mês.</p><div id="co-mensal"></div></section>
            <section class="demo-cartao " aria-labelledby="co-servicos-h"><h2 id="co-servicos-h">O que mais fatura</h2><p class="sub">Faturamento por tipo de serviço.</p><div id="co-servicos"></div></section>
            <section class="demo-cartao " aria-labelledby="co-vendedores-h"><h2 id="co-vendedores-h">Quem vendeu</h2><p class="sub">Faturamento por vendedor, no período inteiro.</p><div id="co-vendedores"></div></section>
            <section class="demo-cartao larga" aria-labelledby="co-dias-h"><h2 id="co-dias-h">Os dias mais fortes</h2><p class="sub">Serviços vendidos por dia da semana.</p><div id="co-dias"></div></section>
        </div>

        @include('partials.demo-chamada')
    </div>
@endsection

@push('scripts')
<script src="/demos/graficos.js?v={{ filemtime(public_path('demos/graficos.js')) }}"></script>
<script src="/demos/comercial.js?v={{ filemtime(public_path('demos/comercial.js')) }}"></script>
@endpush
