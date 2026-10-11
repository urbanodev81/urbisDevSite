@extends('layouts.site')

@section('titulo', 'Atendimento e prazos — demonstração UrbisDev')
@section('descricao', 'Painel de exemplo: chamados por fila, quanto fica dentro do prazo combinado e em que hora a fila aperta. Dados inventados.')

@push('estilos')
@include('partials.demo-estilos')

@endpush

@section('conteudo')
    @include('partials.demo-topo', ['introducao' => 'Oito semanas de chamados de um suporte. Escolha a fila e veja onde o prazo estoura.'])

    <div class="container demo-corpo">
        <div class="demo-filtros">
            <div class="campo"><span class="rotulo" id="at-periodo-r">Período</span><div class="demo-botoes" id="at-periodo" role="group" aria-labelledby="at-periodo-r"><button type="button" data-valor="2" aria-pressed="false">2 semanas</button><button type="button" data-valor="4" aria-pressed="false">4 semanas</button><button type="button" data-valor="8" aria-pressed="true">8 semanas</button></div></div>
            <div class="campo"><label for="at-fila">Fila</label><select id="at-fila"><option value="todas">Todas as filas</option></select></div>
        </div>

        <dl class="demo-numeros">
            <div class="demo-numero"><dt>Chamados</dt><dd id="at-total"></dd><small id="at-total-d"></small></div>
            <div class="demo-numero"><dt>Dentro do prazo</dt><dd id="at-prazo"></dd><small id="at-prazo-d"></small></div>
            <div class="demo-numero"><dt>Primeira resposta</dt><dd id="at-resp"></dd><small id="at-resp-d"></small></div>
            <div class="demo-numero"><dt>Abertos agora</dt><dd id="at-abertos"></dd><small id="at-abertos-d"></small></div>
        </dl>

        <div class="demo-grade">
            <section class="demo-cartao larga" aria-labelledby="at-semanal-h"><h2 id="at-semanal-h">O prazo, semana a semana</h2><p class="sub">Parcela dos chamados respondidos dentro do combinado.</p><div id="at-semanal"></div></section>
            <section class="demo-cartao " aria-labelledby="at-filas-h"><h2 id="at-filas-h">Fila por fila</h2><p class="sub">Dentro do prazo por fila, no período inteiro, da pior para a melhor.</p><div id="at-filas"></div></section>
            <section class="demo-cartao " aria-labelledby="at-calor-h"><h2 id="at-calor-h">Quando a fila aperta</h2><p class="sub">Chamados abertos por dia da semana e hora.</p><div id="at-calor"></div></section>
        </div>

        @include('partials.demo-chamada')
    </div>
@endsection

@push('scripts')
<script src="/demos/graficos.js?v={{ filemtime(public_path('demos/graficos.js')) }}"></script>
<script src="/demos/atendimento.js?v={{ filemtime(public_path('demos/atendimento.js')) }}"></script>
@endpush
