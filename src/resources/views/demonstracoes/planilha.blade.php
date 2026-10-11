@extends('layouts.site')

@section('titulo', 'Suba a sua planilha — demonstração UrbisDev')
@section('descricao', 'Escolha um Excel ou CSV e veja um painel se montar sozinho. O arquivo é lido no seu aparelho e não é enviado.')

@if (! $liberada)
@push('head')
    <script type="module" src="/vendor/altcha/pt-br-3.2.2.js"></script>
    <script type="module" src="/vendor/altcha/altcha-3.2.2.min.js"></script>
@endpush
@endif

@push('estilos')
@include('partials.demo-estilos')
<style>
    @include('partials.formulario-estilos')
    .pl-porta { max-width: 640px; }
    .pl-porta > p { margin-bottom: 18px; color: var(--fg-muted); line-height: 1.6; }
    .pl-soltar { display: grid; gap: 14px; justify-items: start; padding: 24px; background: var(--surface); border: 2px dashed var(--border-input); border-radius: var(--r-card); }
    .pl-soltar.sobre { border-color: var(--accent); }
    .pl-soltar p { color: var(--fg-muted); line-height: 1.6; max-width: 62ch; }
    .pl-acoes { display: flex; flex-wrap: wrap; gap: 12px; }
    .pl-acoes .btn { min-height: 44px; cursor: pointer; }
    .pl-arquivo:focus-within { outline: 2px solid var(--accent); outline-offset: 3px; }
    .pl-oculto { position: absolute; width: 1px; height: 1px; overflow: hidden; clip-path: inset(50%); white-space: nowrap; }
    .pl-status { margin-top: 14px; font-size: .95rem; color: var(--fg-muted); }
    .pl-erro { margin-top: 14px; padding: 12px 14px; border: 1px solid var(--error); border-radius: var(--r-btn); color: var(--fg); }
    .pl-frase { margin: 0 0 20px; font-size: 1.05rem; line-height: 1.6; color: var(--fg); max-width: 70ch; }
    .pl-previa { overflow-x: auto; }
    .pl-previa table { border-collapse: collapse; font-size: .88rem; min-width: 100%; }
    .pl-previa th, .pl-previa td { padding: 8px 12px; text-align: left; border-bottom: 1px solid var(--border); white-space: nowrap; max-width: 28ch; overflow: hidden; text-overflow: ellipsis; }
    .pl-previa th { color: var(--fg-subtle); font-weight: 600; }
    .pl-previa th small { display: block; font-weight: 400; font-family: var(--font-mono); font-size: .68rem; letter-spacing: .08em; text-transform: uppercase; }
</style>
@endpush

@section('conteudo')
    @include('partials.demo-topo', [
        'introducao' => 'Escolha um Excel ou CSV seu. O painel se monta a partir das colunas que encontrar: o que somar, por onde agrupar e, se houver data, como andou mês a mês.',
        'selo' => 'O arquivo é lido no seu aparelho e não é enviado',
    ])

    <div class="container demo-corpo">
        @if (! $liberada)
            <section class="demo-cartao pl-porta" aria-labelledby="pl-porta-h">
                <h2 id="pl-porta-h">Antes de abrir, diga quem é você</h2>
                <p>Esta demonstração trabalha com um arquivo seu, então pedimos o contato primeiro. A planilha não vem junto: ela é aberta só no seu navegador, e nós recebemos apenas o que você escrever aqui.</p>

                <form action="{{ url('/demonstracoes/'.$slug.'/liberar') }}" method="POST">
                    @csrf
                    <div class="field-row">
                        <div class="field">
                            <label for="pl-nome">Nome *</label>
                            <input type="text" id="pl-nome" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                            @error('name') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="field">
                            <label for="pl-email">E-mail *</label>
                            <input type="email" id="pl-email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                            @error('email') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="field">
                        <label for="pl-telefone">Telefone *</label>
                        <input type="tel" id="pl-telefone" name="phone" value="{{ old('phone') }}" placeholder="(11) 99999-9999" required maxlength="15" autocomplete="tel">
                        @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="field">
                        <div class="consent">
                            <input type="checkbox" id="pl-consent" name="consent" value="1" required>
                            <label for="pl-consent">Autorizo o armazenamento dos meus dados para fins de contato, conforme a LGPD.</label>
                        </div>
                        @error('consent') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="field captcha">
                        <altcha-widget challenge="/captcha/desafio" name="captcha_token" language="pt-br" auto="onload" hidefooter hidelogo></altcha-widget>
                        @error('captcha_token') <span class="field-error">{{ $message }}</span> @enderror
                    </div>
                    <div>
                        <button type="submit" class="btn btn-primary">Abrir a demonstração</button>
                    </div>
                </form>
            </section>
        @else
            <section id="ferramenta" aria-labelledby="pl-soltar-h">
                <div class="pl-soltar" id="pl-soltar">
                    <h2 id="pl-soltar-h">Escolha a planilha</h2>
                    <p>Excel (.xlsx) ou CSV, até 5&nbsp;MB, com os títulos das colunas na primeira linha. Nada é enviado: desligue a internet antes de escolher, se quiser conferir.</p>
                    <div class="pl-acoes">
                        <label class="btn btn-primary pl-arquivo" for="pl-arquivo">
                            Escolher arquivo
                            <input class="pl-oculto" type="file" id="pl-arquivo" accept=".xlsx,.csv,.txt,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                        </label>
                        <button type="button" class="btn btn-ghost" id="pl-exemplo">Usar uma planilha de exemplo</button>
                    </div>
                </div>
                <p class="pl-status" id="pl-status" role="status"></p>
                <p class="pl-erro" id="pl-erro" role="alert" hidden></p>
            </section>

            <div id="pl-painel" hidden>
                <div class="demo-filtros" style="margin-top: 24px;">
                    <div class="campo"><label for="pl-valor">O que medir</label><select id="pl-valor"></select></div>
                    <div class="campo"><span class="rotulo" id="pl-conta-r">Como</span><div class="demo-botoes" id="pl-conta" role="group" aria-labelledby="pl-conta-r"><button type="button" data-valor="soma" aria-pressed="true">Soma</button><button type="button" data-valor="media" aria-pressed="false">Média</button></div></div>
                    <div class="campo"><label for="pl-grupo">Agrupar por</label><select id="pl-grupo"></select></div>
                    <div class="campo" id="pl-data-campo"><label for="pl-data">Data</label><select id="pl-data"></select></div>
                </div>

                <p class="pl-frase" id="pl-frase"></p>

                <dl class="demo-numeros">
                    <div class="demo-numero"><dt>Linhas lidas</dt><dd id="pl-linhas"></dd><small id="pl-linhas-d"></small></div>
                    <div class="demo-numero"><dt id="pl-n1-t">Total</dt><dd id="pl-n1"></dd><small id="pl-n1-d"></small></div>
                    <div class="demo-numero"><dt>Média por linha</dt><dd id="pl-n2"></dd><small id="pl-n2-d"></small></div>
                    <div class="demo-numero"><dt>Maior valor</dt><dd id="pl-n3"></dd><small id="pl-n3-d"></small></div>
                </dl>

                <div class="demo-grade">
                    <section class="demo-cartao larga" id="pl-tempo-cartao" aria-labelledby="pl-tempo-h"><h2 id="pl-tempo-h">Mês a mês</h2><p class="sub" id="pl-tempo-sub"></p><div id="pl-tempo"></div></section>
                    <section class="demo-cartao larga" id="pl-grupos-cartao" aria-labelledby="pl-grupos-h"><h2 id="pl-grupos-h">Por grupo</h2><p class="sub" id="pl-grupos-sub"></p><div id="pl-grupos"></div></section>
                    <section class="demo-cartao larga" aria-labelledby="pl-previa-h"><h2 id="pl-previa-h">O que foi lido</h2><p class="sub" id="pl-previa-sub"></p><div class="pl-previa" id="pl-previa" tabindex="0" role="region" aria-labelledby="pl-previa-h"></div></section>
                </div>
            </div>

            @include('partials.demo-chamada', ['chamada' => 'Isto é o começo: com o seu sistema ou a sua planilha de verdade, o painel ganha as regras do seu negócio, metas, filtros e atualização sozinha.'])
        @endif
    </div>
@endsection

@if ($liberada)
@push('scripts')
<script src="/demos/graficos.js?v={{ filemtime(public_path('demos/graficos.js')) }}"></script>
<script src="/demos/planilha.js?v={{ filemtime(public_path('demos/planilha.js')) }}"></script>
@endpush
@endif
