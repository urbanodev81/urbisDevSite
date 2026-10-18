{{--
    Home do site — a "cidade à noite".

    O casco (head, header, rodapé, acessibilidade, flutuantes, rolagem suave)
    saiu daqui em 23/08/2026 para `layouts/site.blade.php`. Ficou o que é
    DESTA página: a grade de janelas acesas, os distritos (as frentes) e o
    formulário de contato. "Próximos passos" saiu em 18/10/2026: anunciava como
    planejado o white-label que já estava no ar.

    A extração pagou na hora: as correções de alvo de toque de 44px tinham
    sido feitas só na página "Como trabalhamos", e a home tinha ficado com
    `.nav-toggle` de 8px de padding, link de menu sem altura mínima e ícone de
    rede social de 38px. Não era decisão, era cópia envelhecendo sozinha —
    agora o casco é um só e a correção vale nas duas.
--}}
@extends('layouts.site')

@section('titulo', 'UrbisDev — Software que resolve problemas reais')
@section('descricao', 'Sites com painel próprio e sistemas de gestão para imobiliárias, clínicas, sindicatos, oficinas e artistas — do primeiro esboço ao app no celular.')

@push('head')
    {{-- Altcha auto-hospedado (29/09/2026; era o Turnstile da Cloudflare).
         A tradução vem ANTES do widget: ela registra o pt-br no objeto global
         que o widget lê ao montar. Os dois são módulos, que já executam em
         ordem e depois do HTML — sem `defer` a acrescentar. --}}
    <script type="module" src="/vendor/altcha/pt-br-3.2.2.js"></script>
    <script type="module" src="/vendor/altcha/altcha-3.2.2.min.js"></script>
@endpush

@push('estilos')
@include('partials.trabalho-estilos')
<style>
        /* ---------- hero + grade de janelas ---------- */
        .hero { position: relative; overflow: hidden; padding-block: clamp(88px, 12vw, 150px); }
        /* O `#000` das duas linhas de máscara abaixo NÃO é cor: `mask-image` lê
           só o canal alfa, e ali o preto significa "opaco". Tokenizar faria a
           máscara depender do tema sem nenhum motivo. */
        .citygrid { position: absolute; inset: 0; display: grid; grid-template-columns: repeat(auto-fill, minmax(26px, 1fr)); grid-auto-rows: 26px; gap: 0; opacity: .9; pointer-events: none;
            -webkit-mask-image: radial-gradient(ellipse 90% 85% at 50% 20%, #000 30%, transparent 78%);
            mask-image: radial-gradient(ellipse 90% 85% at 50% 20%, #000 30%, transparent 78%); }
        .citygrid i { border-right: 1px solid var(--grid-line); border-bottom: 1px solid var(--grid-line); }
        .citygrid i.lit { position: relative; }
        .citygrid i.lit::after { content: ""; position: absolute; inset: 9px; background: var(--amber-500); opacity: 0; animation: window var(--dur, 7s) ease-in-out var(--delay, 0s) infinite; }
        @keyframes window { 0%, 100% { opacity: 0; } 12% { opacity: .85; } 55% { opacity: .5; } 70% { opacity: 0; } }
        .hero-inner { position: relative; max-width: 820px; }
        .hero h1 { margin: 20px 0 22px; }
        .hero h1 em { font-style: normal; color: var(--amber-500); }
        .hero p { max-width: 56ch; font-size: 1.15rem; }
        .hero-ctas { display: flex; flex-wrap: wrap; gap: 14px; margin-top: 36px; }
        .hero-meta { margin-top: 56px; display: flex; flex-wrap: wrap; gap: 12px 32px; font-family: var(--font-mono); font-size: .75rem; letter-spacing: .12em; text-transform: uppercase; color: var(--text-low); }
        .hero-meta span { display: inline-flex; align-items: center; gap: 8px; }
        .hero-meta svg { color: var(--amber-500); }

        /* ---------- demonstrações ---------- */
        .trabalho-de-tipo { display: flex; flex-direction: column; gap: 10px; }
        .trabalho-de-tipo .trabalho { flex: 1; }
        .trabalho-de-tipo .tag { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: var(--fg-subtle); }
        .trabalho-de-tipo .ver-mais { align-self: flex-start; display: inline-flex; align-items: center; min-height: 44px; font-weight: 600; color: var(--accent); text-decoration: none; }
        .trabalho-de-tipo .ver-mais:hover { text-decoration: underline; }
        .demos-home { display: grid; gap: 20px; }
        @media (min-width: 880px) { .demos-home { grid-template-columns: repeat(3, 1fr); } }
        .demo-home { display: flex; flex-direction: column; gap: 10px; padding: 24px; background: var(--surface); border: 1px solid var(--border); border-radius: var(--r-card); text-decoration: none; color: var(--fg-muted); transition: border-color .18s ease-out, transform .18s ease-out; }
        .demo-home:hover { border-color: var(--accent); transform: translateY(-3px); }
        .demo-home .tag { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: var(--fg-subtle); }
        .demo-home p { font-size: .98rem; line-height: 1.6; }
        .demo-home .abrir { margin-top: auto; padding-top: 6px; font-weight: 600; color: var(--accent); }
        @media (prefers-reduced-motion: reduce) { .demo-home { transition: none; } .demo-home:hover { transform: none; } }

        /* ---------- seções / cards ---------- */
        .section-head { max-width: 640px; margin-bottom: clamp(40px, 6vw, 64px); }
        .section-head h2 { margin: 16px 0 14px; }
        .districts { display: grid; gap: 20px; }
        @media (min-width: 720px) { .districts { grid-template-columns: repeat(2, 1fr); } }
        .district { position: relative; background: var(--ink-800); border: 1px solid var(--line); border-radius: var(--r-card); padding: 28px 26px 26px; display: flex; flex-direction: column; gap: 14px; transition: transform .2s ease-out, border-color .2s ease-out; color: var(--text-mid); }
        .district::before { content: ""; position: absolute; top: 0; left: 24px; right: 24px; height: 3px; border-radius: 0 0 3px 3px; background: var(--dc, var(--amber-500)); }
        .district:hover { transform: translateY(-3px); border-color: color-mix(in srgb, var(--dc, var(--amber-500)) 40%, var(--line)); }
        .district .tag { font-family: var(--font-mono); font-size: .72rem; letter-spacing: .14em; text-transform: uppercase; color: var(--text-low); }
        .district .tag b { color: var(--dc, var(--amber-500)); font-weight: 500; }
        .district h3 { font-family: var(--font-display); font-size: 1.5rem; font-weight: 700; }
        .district p { font-size: .98rem; flex: 1; }
        .district .chip { align-self: flex-start; }
        .district .card-icon { color: var(--dc, var(--amber-500)); }
        .chip { display: inline-flex; align-items: center; gap: 7px; font-family: var(--font-mono); font-size: .7rem; letter-spacing: .1em; text-transform: uppercase; border: 1px solid var(--line); border-radius: 999px; padding: 5px 12px; color: var(--text-mid); }
        .chip::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--ok); }
        .chip--dev::before { background: var(--warn); }

        /* ---------- contato ---------- */
        .contact { border-top: 1px solid var(--line); background: var(--ink-800); }
        .contact-grid { display: grid; gap: 48px; }
        @media (min-width: 880px) { .contact-grid { grid-template-columns: 1fr 1.2fr; gap: 80px; } }
        .contact-info p { margin: 14px 0 28px; max-width: 44ch; }
        /* 44px de altura: são LINKS (mailto: e o site), e estavam com 27px —
           a última sobra da dívida de alvo de toque da home. */
        .contact-line { display: flex; align-items: center; gap: 12px; min-height: 44px; font-size: .98rem; color: var(--text-hi); margin-bottom: 12px; text-decoration: none; }
        .contact-line svg { color: var(--amber-500); flex: none; }
        .contact-line:hover { color: var(--amber-400); }
        @include('partials.formulario-estilos')
        .char-count { font-family: var(--font-mono); font-size: .72rem; color: var(--text-low); text-align: right; }

        /* ---------- modal de feedback ---------- */
        .modal-overlay { position: fixed; inset: 0; z-index: 9999; background: var(--overlay); backdrop-filter: blur(3px); display: flex; align-items: center; justify-content: center; opacity: 0; pointer-events: none; transition: opacity .3s ease-out; }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal { background: var(--ink-800); border: 1px solid var(--line); border-radius: var(--r-card); padding: 40px 32px 32px; max-width: 440px; width: 90%; text-align: center; transform: translateY(20px); transition: transform .3s ease-out; }
        .modal-overlay.active .modal { transform: translateY(0); }
        .modal-icon { width: 64px; height: 64px; border-radius: 50%; border: 1px solid var(--line); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 20px; }
        .modal-icon.success { color: var(--ok); border-color: color-mix(in srgb, var(--ok) 40%, var(--line)); }
        .modal-icon.error { color: var(--error); border-color: color-mix(in srgb, var(--error) 40%, var(--line)); }
        .modal h3 { font-family: var(--font-display); font-size: 1.35rem; font-weight: 700; margin-bottom: 8px; }
        .modal p { color: var(--text-mid); font-size: .98rem; margin-bottom: 24px; }
</style>
@endpush

@section('conteudo')

        {{-- Hero --}}
        <section class="hero" aria-label="Apresentação">
            <div class="citygrid" id="citygrid" aria-hidden="true"></div>
            <div class="container hero-inner">
                <span class="eyebrow">Sites · Sistemas · Aplicativos</span>
                <h1>Software que resolve <em>problemas reais</em>.</h1>
                <p>Sites com painel próprio e sistemas de gestão para imobiliárias, clínicas, sindicatos, oficinas e artistas — do primeiro esboço ao app no celular.</p>
                <div class="hero-ctas">
                    <a class="btn btn-primary" href="#contato">Fale conosco
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                    <a class="btn btn-ghost" href="#demonstracoes">Veja funcionando</a>
                </div>
            </div>
        </section>

        {{-- O que fazemos --}}
        <section id="fazemos">
            <div class="container">
                <div class="section-head reveal">
                    <span class="eyebrow">O que fazemos</span>
                    <h2>Soluções digitais sob medida para o seu negócio.</h2>
                </div>
                {{-- Quatro frentes: duas no ar, duas em construção. Sem nome de
                     produto e sem dizer para quem é a obra (decisão de 18/10/2026). --}}
                <div class="districts">
                    <div class="district reveal" style="--dc:var(--d-urb)">
                        <span class="tag">Frente · <b>Imobiliário</b></span>
                        <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-4h6v4"/><path d="M9 10h.01"/><path d="M15 10h.01"/><path d="M9 14h.01"/><path d="M15 14h.01"/></svg>
                        <h3>Imobiliárias</h3>
                        <p>Site com busca de imóveis, fichas de lançamento e blog, e o sistema de gestão por trás: cadastro do acervo, contatos dos interessados e a sua marca em tudo.</p>
                        <span class="chip">No ar</span>
                    </div>
                    <div class="district reveal" style="--dc:var(--amber-500)">
                        <span class="tag">Frente · <b>Sites com painel</b></span>
                        <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M3 9h18"/><path d="M8 14h4"/><path d="M8 17h8"/></svg>
                        <h3>Sites que você mesmo atualiza</h3>
                        <p>Para bandas, artistas, oficinas e profissionais: agenda, fotos, vídeos e textos trocados pelo painel, sem depender de ninguém, com o site rápido e acessível.</p>
                        <span class="chip">No ar</span>
                    </div>
                    <div class="district reveal" style="--dc:var(--d-synapse)">
                        <span class="tag">Frente · <b>Saúde</b></span>
                        <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5l7 7Z"/></svg>
                        <h3>Clínicas de terapia</h3>
                        <p>Gestão da clínica com agenda, evolução de cada paciente e cuidado com o dado de saúde desde o desenho.</p>
                        <span class="chip chip--dev">Em construção</span>
                    </div>
                    <div class="district reveal" style="--dc:var(--d-ssb)">
                        <span class="tag">Frente · <b>Associações</b></span>
                        <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <h3>Sindicatos e associações</h3>
                        <p>Cadastro de associados, mensalidades, comunicação com a base e votações, num sistema só.</p>
                        <span class="chip chip--dev">Em construção</span>
                    </div>
                </div>
            </div>
        </section>

        {{-- Tipos de site: um trabalho de cada categoria do portfólio, que vem do
             Sócrates (App\Support\Portfolio). Sem nenhum marcado lá, a seção
             não existe. A lista inteira mora em /trabalhos. --}}
        @if (! empty($trabalhos))
        <section id="trabalhos">
            <div class="container">
                <div class="section-head reveal">
                    <span class="eyebrow">Trabalhos</span>
                    <h2>Cada tipo de site, com um exemplo que já fizemos.</h2>
                    <p>Um de cada tipo. Os outros estão na página de trabalhos.</p>
                </div>
                <div class="trabalhos reveal">
                    @foreach ($trabalhos as $categoria)
                        <div class="trabalho-de-tipo">
                            <p class="tag">{{ $categoria['nome'] }}</p>
                            @include('partials.trabalho-cartao', ['item' => $categoria['itens'][0], 'categoria' => $categoria, 'nivel' => 'h3'])
                            @if (count($categoria['itens']) > 1)
                                <a class="ver-mais" href="{{ url('/trabalhos#'.$categoria['chave']) }}">Ver mais<span class="sr-only"> em {{ $categoria['nome'] }}</span>&nbsp;&rarr;</a>
                            @endif
                        </div>
                    @endforeach
                </div>
                <p class="reveal" style="margin-top:28px"><a class="btn btn-ghost" href="{{ url('/trabalhos') }}">Ver outros sites</a></p>
            </div>
        </section>
        @endif

        {{-- Demonstrações: as três primeiras de `config/demonstracoes.php`.
             A lista inteira mora em /demonstracoes. --}}
        <section id="demonstracoes">
            <div class="container">
                <div class="section-head reveal">
                    <span class="eyebrow">Demonstrações</span>
                    <h2>Veja funcionando antes de pedir.</h2>
                    <p>Painéis de verdade, com dados inventados. Clique, filtre e imagine com os números do seu negócio.</p>
                </div>
                <div class="demos-home reveal">
                    @foreach (collect($demos ?? [])->where('pronta', true)->take(3) as $slug => $demo)
                        <a class="demo-home" href="{{ url('/demonstracoes/'.$slug) }}">
                            <span class="tag">{{ $demo['tipo'] }}</span>
                            <h3>{{ $demo['titulo'] }}</h3>
                            <p>{{ $demo['resumo'] }}</p>
                            <span class="abrir">Abrir a demonstração &rarr;</span>
                        </a>
                    @endforeach
                </div>
                <p class="reveal" style="margin-top:28px"><a class="btn btn-ghost" href="{{ url('/demonstracoes') }}">Ver todas as demonstrações</a></p>
            </div>
        </section>

        {{-- Contato --}}
        <section id="contato" class="contact">
            <div class="container contact-grid">
                <div class="contact-info reveal">
                    <span class="eyebrow">Contato</span>
                    <h2 style="margin-top:16px">Tem um projeto em mente? Vamos conversar.</h2>
                    <p>Conte pra gente o que você precisa. Preencha o formulário e retornamos o contato.</p>
                    <a class="contact-line" href="mailto:contato@urbisdev.tech">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg>
                        contato@urbisdev.tech
                    </a>
                    <a class="contact-line" href="https://urbisdev.tech">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M12 2a14.5 14.5 0 0 0 0 20 14.5 14.5 0 0 0 0-20"/><path d="M2 12h20"/></svg>
                        urbisdev.tech
                    </a>
                </div>

                <form action="/contato" method="POST" class="reveal">
                    @csrf

                    <div class="field-row">
                        <div class="field">
                            <label for="name">Nome *</label>
                            <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="255" autocomplete="name">
                            @error('name') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                        <div class="field">
                            <label for="email">E-mail *</label>
                            <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                            @error('email') <span class="field-error">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="field">
                        <label for="phone">Telefone *</label>
                        <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="(11) 99999-9999" required maxlength="15" autocomplete="tel">
                        @error('phone') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <label for="message">Mensagem *</label>
                        <textarea id="message" name="message" required minlength="50" maxlength="5000" placeholder="Conte-nos sobre seu projeto (mínimo 50 caracteres)...">{{ old('message', $recadoDaDemo ?? '') }}</textarea>
                        <div class="char-count"><span id="charCount">0</span>/5000</div>
                        @error('message') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div class="field">
                        <div class="consent">
                            <input type="checkbox" id="consent" name="consent" value="1" required>
                            <label for="consent">Autorizo o armazenamento dos meus dados para fins de contato, conforme a LGPD.</label>
                        </div>
                        @error('consent') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    {{-- `name="captcha_token"`: o próprio widget mantém o campo
                         oculto com a solução — nenhum JS de cola no envio.
                         `auto="onload"` resolve o desafio sozinho ao abrir a
                         página; quando a pessoa termina de escrever, já passou. --}}
                    <div class="field captcha">
                        <altcha-widget
                            challenge="/captcha/desafio"
                            name="captcha_token"
                            language="pt-br"
                            auto="onload"
                            hidefooter
                            hidelogo
                        ></altcha-widget>
                        @error('captcha_token') <span class="field-error">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <button type="submit" class="btn btn-primary">Enviar mensagem</button>
                        @error('envio') <span class="field-error" role="alert">{{ $message }}</span> @enderror
                    </div>
                </form>
            </div>
        </section>
@endsection

@section('flutuantes')
    {{-- Modal feedback --}}
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="modal-icon" id="modalIcon"></div>
            <h3 id="modalTitle"></h3>
            <p id="modalText"></p>
            <button class="btn btn-primary" onclick="document.getElementById('modalOverlay').classList.remove('active')">Fechar</button>
        </div>
    </div>
@endsection

@push('scripts')
<script>
        // Grade de janelas acesas (assinatura da marca)
        (function () {
            var grid = document.getElementById('citygrid');
            var cols = Math.ceil(window.innerWidth / 26) + 1;
            var rows = Math.ceil(Math.min(window.innerHeight, 720) / 26) + 1;
            var total = cols * rows;
            var frag = document.createDocumentFragment();
            for (var i = 0; i < total; i++) {
                var cell = document.createElement('i');
                if (Math.random() < 0.045) { // ~4,5% das janelas acendem
                    cell.className = 'lit';
                    cell.style.setProperty('--delay', (Math.random() * 12).toFixed(1) + 's');
                    cell.style.setProperty('--dur', (6 + Math.random() * 8).toFixed(1) + 's');
                }
                frag.appendChild(cell);
            }
            grid.appendChild(frag);
        })();

        // Máscara de telefone
        var phone = document.getElementById('phone');
        phone.addEventListener('input', function (e) {
            var v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length === 0) { e.target.value = ''; return; }
            if (v.length <= 2) e.target.value = '(' + v;
            else if (v.length <= 7) e.target.value = '(' + v.slice(0, 2) + ') ' + v.slice(2);
            else e.target.value = '(' + v.slice(0, 2) + ') ' + v.slice(2, 7) + '-' + v.slice(7);
        });

        // Contador de caracteres
        var msg = document.getElementById('message');
        var counter = document.getElementById('charCount');
        msg.addEventListener('input', function () { counter.textContent = msg.value.length; });
        counter.textContent = msg.value.length;

        // Trava o botão no envio.
        //
        // O envio é SÍNCRONO (o SMTP do Zoho acontece dentro da requisição), e
        // enquanto ele acontece a página fica parada, sem sinal nenhum de que
        // algo está em curso. A pessoa clica de novo — e o segundo POST manda
        // um segundo e-mail, porque abortar a navegação não desfaz o que o
        // servidor já fez. Reproduzido em 23/08/2026: dois cliques com 1,5 s
        // de intervalo, duas mensagens na caixa.
        //
        // Isto aqui resolve o caso comum e, principalmente, DÁ RESPOSTA: o
        // botão vira "Enviando…" e a pessoa para de clicar. O que garante que
        // não sai duplicado é o servidor (ver ContactController) — este
        // bloqueio não existe com JavaScript desligado.
        var formContato = document.querySelector('form[action="/contato"]');
        var btnEnviar = formContato.querySelector('button[type="submit"]');
        var textoBotao = btnEnviar.textContent;

        formContato.addEventListener('submit', function () {
            btnEnviar.disabled = true;
            btnEnviar.textContent = 'Enviando…';
            // Leitor de tela não anuncia troca de texto de botão sozinho.
            btnEnviar.setAttribute('aria-busy', 'true');
        });

        // Voltar pelo histórico devolve a página do cache do navegador com o
        // botão ainda travado — e aí ela não envia mais nada, sem dizer por
        // quê. `pageshow` é o único evento que dispara nesse caminho.
        window.addEventListener('pageshow', function (e) {
            if (e.persisted) {
                btnEnviar.disabled = false;
                btnEnviar.textContent = textoBotao;
                btnEnviar.removeAttribute('aria-busy');
            }
        });

        // Modal de feedback (sessão)
        var overlay = document.getElementById('modalOverlay');
        var icon = document.getElementById('modalIcon');
        var modalTitle = document.getElementById('modalTitle');
        var modalText = document.getElementById('modalText');

        @if (session('success'))
        overlay.classList.add('active');
        icon.className = 'modal-icon success';
        icon.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>';
        modalTitle.textContent = 'Mensagem enviada!';
        modalText.textContent = '{{ session('success') }}';
        @endif

        @if (session('error'))
        overlay.classList.add('active');
        icon.className = 'modal-icon error';
        icon.innerHTML = '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>';
        modalTitle.textContent = 'Ops!';
        modalText.textContent = '{{ session('error') }}';
        @endif
</script>
@endpush
