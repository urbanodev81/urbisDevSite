@extends('layouts.site')

@section('titulo', 'Galeria de sites — demonstração UrbisDev')
@section('descricao', 'Página de exemplo: escolha o tipo de site, troque a cor, o nome e o logo e veja o resultado na hora. O logo não sai do seu aparelho.')

@push('estilos')
@include('partials.demo-estilos')
<style>
    .gs-controles { display: grid; gap: 14px 20px; }
    @media (min-width: 880px) { .gs-controles { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    .gs-controles .campo { display: grid; gap: 4px; min-width: 0; }
    .gs-controles input[type=text] { min-height: 44px; padding: 0 12px; background: var(--bg); color: var(--fg); border: 1px solid var(--border-input); border-radius: var(--r-btn); font: inherit; font-size: 1rem; width: 100%; }
    .gs-cores { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
    .gs-cor { width: 44px; height: 44px; border-radius: 50%; border: 2px solid var(--border-input); background: var(--c); cursor: pointer; }
    .gs-cor[aria-pressed="true"] { outline: 3px solid var(--fg); outline-offset: 2px; }
    .gs-cores input[type=color] { width: 44px; height: 44px; padding: 0; border: 2px solid var(--border-input); border-radius: 8px; background: none; cursor: pointer; }
    .gs-logo { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 12px; }
    .gs-logo input[type=file] { max-width: 100%; min-height: 44px; font: inherit; font-size: .9rem; color: var(--fg-muted); }
    .gs-cnpj { display: flex; gap: 8px; }
    .gs-cnpj input { flex: 1; min-width: 0; }
    .gs-cnpj-nota { display: block; margin-top: 6px; font-size: .8rem; color: var(--fg-muted); }
    .gs-nota { margin-top: 4px; font-size: .82rem; color: var(--fg-subtle); }
    .demo-botoes { flex-wrap: wrap; }

    /* O site de exemplo: cores próprias, de propósito fora dos tokens da UrbisDev.
       --m é a cor da marca do visitante; --sobre-m é o texto que cabe sobre ela. */
    .gs-janela { margin-top: 20px; border: 1px solid var(--border-input); border-radius: 12px; overflow: hidden; box-shadow: var(--shadow-painel); }
    .gs-barra { display: flex; align-items: center; gap: 12px; padding: 10px 14px; background: var(--surface-raised); border-bottom: 1px solid var(--border); }
    .gs-barra i { width: 10px; height: 10px; border-radius: 50%; background: var(--border-input); flex: none; }
    .gs-barra span { flex: 1; min-width: 0; padding: 4px 12px; background: var(--bg); border-radius: 999px; font-family: var(--font-mono); font-size: .76rem; color: var(--fg-muted); overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gs-site { --fundo: #ffffff; --tinta: #16181d; --suave: #5b6270; --linha: #e3e6eb; --faixa: #f4f5f7; background: var(--fundo); color: var(--tinta); font-family: var(--font-body); }
    .gs-site.escuro { --fundo: #101216; --tinta: #f1f3f6; --suave: #a3aab6; --linha: #2a2e36; --faixa: #181b21; }
    .gs-site * { color: inherit; }
    .gs-topo { display: flex; align-items: center; justify-content: space-between; gap: 16px; padding: 14px clamp(16px, 4vw, 36px); border-bottom: 1px solid var(--linha); }
    .gs-marca { display: flex; align-items: center; gap: 10px; min-width: 0; font-family: var(--font-display); font-weight: 700; font-size: 1.1rem; }
    .gs-marca img { height: 34px; width: auto; max-width: 120px; object-fit: contain; }
    .gs-marca img[hidden] { display: none; }
    .gs-marca .sinal { display: grid; place-items: center; width: 34px; height: 34px; border-radius: 8px; background: var(--m); color: var(--sobre-m); flex: none; }
    .gs-marca .sinal[hidden] { display: none; }
    .gs-marca b { overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    .gs-menu { display: none; gap: 20px; list-style: none; font-size: .9rem; color: var(--suave); }
    @media (min-width: 720px) { .gs-menu { display: flex; } }
    .gs-capa { display: grid; gap: 24px; align-items: center; padding: clamp(28px, 6vw, 64px) clamp(16px, 4vw, 36px); }
    @media (min-width: 720px) { .gs-capa { grid-template-columns: 1.1fr .9fr; } }
    .gs-capa .chamada { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: var(--suave); }
    .gs-capa h2 { margin: 10px 0 12px; font-size: clamp(1.7rem, 4vw, 2.6rem); line-height: 1.1; color: var(--tinta); }
    .gs-capa p { max-width: 46ch; color: var(--suave); line-height: 1.6; }
    .gs-acoes { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 20px; }
    .gs-botao { display: inline-flex; align-items: center; min-height: 44px; padding: 0 20px; border-radius: 8px; font-weight: 600; font-size: .95rem; background: var(--m); color: var(--sobre-m); }
    .gs-botao.vazado { background: transparent; color: var(--tinta); border: 1px solid var(--linha); }
    .gs-quadro { aspect-ratio: 4 / 3; border-radius: 14px; background: linear-gradient(135deg, var(--m), color-mix(in srgb, var(--m) 45%, var(--fundo))); display: grid; place-items: center; }
    .gs-quadro svg { width: 38%; height: auto; color: var(--sobre-m); opacity: .9; }
    .gs-itens { display: grid; gap: 12px; padding: 0 clamp(16px, 4vw, 36px) clamp(24px, 4vw, 40px); list-style: none; }
    @media (min-width: 720px) { .gs-itens { grid-template-columns: repeat(3, 1fr); } }
    .gs-itens li { padding: 16px; background: var(--faixa); border: 1px solid var(--linha); border-top: 3px solid var(--m); border-radius: 10px; }
    .gs-itens strong { display: block; margin-bottom: 4px; color: var(--tinta); }
    .gs-itens span { font-size: .9rem; line-height: 1.5; color: var(--suave); }
    .gs-pe { padding: 14px clamp(16px, 4vw, 36px); border-top: 1px solid var(--linha); font-size: .8rem; color: var(--suave); }
</style>
@endpush

@section('conteudo')
    @include('partials.demo-topo', ['introducao' => 'Escolha o tipo de negócio, ponha o seu nome, a sua cor e o seu logo. O site de exemplo se veste na hora: é assim que a primeira conversa com a gente começa.'])

    <div class="container demo-corpo">
        <div class="demo-filtros gs-controles">
            <div class="campo"><span class="rotulo" id="gs-tipo-r">Tipo de site</span><div class="demo-botoes" id="gs-tipo" role="group" aria-labelledby="gs-tipo-r"></div></div>
            <div class="campo">
                <label for="gs-cnpj">CNPJ (opcional)</label>
                <form class="gs-cnpj" id="gs-cnpj-form" novalidate><input type="text" id="gs-cnpj" inputmode="numeric" maxlength="18" autocomplete="off" placeholder="00.000.000/0000-00" aria-describedby="gs-cnpj-nota"><button type="submit" class="btn btn-ghost">Preencher</button></form>
                <small class="gs-cnpj-nota" id="gs-cnpj-nota" aria-live="polite">Nome, cidade e ramo saem dos dados públicos da Receita. Nada fica guardado.</small>
            </div>
            <div class="campo"><label for="gs-nome">Nome do negócio</label><input type="text" id="gs-nome" maxlength="32" autocomplete="off"></div>
            <div class="campo"><span class="rotulo" id="gs-cor-r">Cor da marca</span><div class="gs-cores" id="gs-cores" role="group" aria-labelledby="gs-cor-r"><input type="color" id="gs-cor-livre" aria-label="Outra cor" value="#c2410c"></div></div>
            <div class="campo">
                <label for="gs-logo">Logo (opcional)</label>
                <div class="gs-logo"><input type="file" id="gs-logo" accept="image/png,image/jpeg,image/webp,image/svg+xml"><button type="button" class="btn btn-ghost" id="gs-logo-tirar" hidden>Tirar o logo</button></div>
                <p class="gs-nota">A imagem é aberta só no seu navegador. Não é enviada a lugar nenhum.</p>
            </div>
            <div class="campo"><span class="rotulo" id="gs-tema-r">Fundo do site</span><div class="demo-botoes" id="gs-tema" role="group" aria-labelledby="gs-tema-r"><button type="button" data-valor="claro" aria-pressed="true">Claro</button><button type="button" data-valor="escuro" aria-pressed="false">Escuro</button></div></div>
        </div>

        <div class="gs-janela" role="img" aria-labelledby="gs-descricao">
            <div class="gs-barra" aria-hidden="true"><i></i><i></i><i></i><span id="gs-endereco"></span></div>
            <div class="gs-site" id="gs-site" aria-hidden="true">
                <div class="gs-topo">
                    <div class="gs-marca"><img id="gs-logo-img" alt="" hidden><span class="sinal" id="gs-sinal"></span><b id="gs-marca-nome"></b></div>
                    <ul class="gs-menu" id="gs-menu"></ul>
                </div>
                <div class="gs-capa">
                    <div>
                        <span class="chamada" id="gs-chamada"></span>
                        <h2 id="gs-frase"></h2>
                        <p id="gs-texto"></p>
                        <div class="gs-acoes"><span class="gs-botao" id="gs-acao"></span><span class="gs-botao vazado" id="gs-acao2"></span></div>
                    </div>
                    <div class="gs-quadro" id="gs-quadro"></div>
                </div>
                <ul class="gs-itens" id="gs-itens"></ul>
                <div class="gs-pe" id="gs-pe"></div>
            </div>
        </div>
        <p class="gs-nota" id="gs-descricao" aria-live="polite"></p>

        @include('partials.demo-chamada', ['chamada' => 'Montamos o site do seu negócio com a sua marca, o seu conteúdo e um painel para você mesmo atualizar.'])
    </div>
@endsection

@push('scripts')
<script src="/demos/graficos.js?v={{ filemtime(public_path('demos/graficos.js')) }}"></script>
<script src="/demos/sites.js?v={{ filemtime(public_path('demos/sites.js')) }}"></script>
@endpush
