@extends('layouts.site')

@section('titulo', 'Funil de uma imobiliária — demonstração UrbisDev')
@section('descricao', 'Painel de exemplo: leads por origem, tempo até a primeira resposta, funil de visita a proposta e anúncios que não convertem. Dados inventados.')

@push('estilos')
@include('partials.demo-estilos')
<style>
    #im-imoveis th small { display: block; font-family: var(--font-mono); font-size: .72rem; color: var(--fg-subtle); }
    #im-imoveis th { white-space: normal; min-width: 180px; }
    #im-imoveis .im-barra { min-width: 170px; }
    #im-imoveis .im-barra i { display: inline-block; height: 10px; max-width: 110px; margin-right: 8px; border-radius: 0 4px 4px 0; background: var(--serie-1); vertical-align: middle; }
    #im-imoveis .im-barra { text-align: left; }
    .im-alerta { display: inline-block; margin-left: 8px; padding: 2px 8px; border: 1px solid var(--warn); border-radius: 999px; font-style: normal; font-size: .72rem; color: var(--fg); }
</style>
@endpush

@section('conteudo')
    @include('partials.demo-topo', ['introducao' => 'Doze semanas de leads de uma imobiliária. Veja onde eles nascem, quanto esperam e onde param.'])

    <div class="container demo-corpo">
        <div class="demo-filtros">
            <div class="campo"><span class="rotulo" id="im-periodo-r">Período</span><div class="demo-botoes" id="im-periodo" role="group" aria-labelledby="im-periodo-r"><button type="button" data-valor="4" aria-pressed="false">4 semanas</button><button type="button" data-valor="8" aria-pressed="false">8 semanas</button><button type="button" data-valor="12" aria-pressed="true">12 semanas</button></div></div>
            <div class="campo"><label for="im-origem">Origem</label><select id="im-origem"><option value="todas">Todas as origens</option></select></div>
        </div>

        <dl class="demo-numeros">
            <div class="demo-numero"><dt>Leads recebidos</dt><dd id="im-leads"></dd><small id="im-leads-d"></small></div>
            <div class="demo-numero"><dt>Primeira resposta</dt><dd id="im-resp"></dd><small id="im-resp-d"></small></div>
            <div class="demo-numero"><dt>Chegaram à visita</dt><dd id="im-visita"></dd><small id="im-visita-d"></small></div>
            <div class="demo-numero"><dt>Fecharam negócio</dt><dd id="im-fecha"></dd><small id="im-fecha-d"></small></div>
        </dl>

        <div class="demo-grade">
            <section class="demo-cartao " aria-labelledby="im-funil-h"><h2 id="im-funil-h">O funil</h2><p class="sub">Quantos leads chegam a cada etapa.</p><div id="im-funil"></div></section>
            <section class="demo-cartao " aria-labelledby="im-origens-h"><h2 id="im-origens-h">De onde vêm</h2><p class="sub">Leads por origem, no período inteiro. Passe o mouse para ver quantos fecharam.</p><div id="im-origens"></div></section>
            <section class="demo-cartao larga" aria-labelledby="im-resposta-h"><h2 id="im-resposta-h">Quanto o lead espera</h2><p class="sub">Tempo até a primeira resposta, semana a semana.</p><div id="im-resposta"></div></section>
            <section class="demo-cartao larga" aria-labelledby="im-h-imoveis"><h2 id="im-h-imoveis">Vistos contra contatados</h2><p class="sub">Anúncio muito visto e pouco contatado costuma ter foto ruim ou preço fora.</p><div class="demo-tabela-rolagem"><table class="demo-tabela" id="im-imoveis"><thead><tr><th scope="col">Imóvel</th><th scope="col">Visitas ao anúncio</th><th scope="col">Contatos</th><th scope="col">Conversão</th></tr></thead><tbody></tbody></table></div></section>
        </div>

        @include('partials.demo-chamada')
    </div>
@endsection

@push('scripts')
<script src="/demos/graficos.js?v={{ filemtime(public_path('demos/graficos.js')) }}"></script>
<script src="/demos/imobiliaria.js?v={{ filemtime(public_path('demos/imobiliaria.js')) }}"></script>
@endpush
