{{--
    Home do site — a "cidade à noite".

    O casco (head, header, rodapé, acessibilidade, flutuantes, rolagem suave)
    saiu daqui em 23/08/2026 para `layouts/site.blade.php`. Ficou o que é
    DESTA página: a grade de janelas acesas, os distritos, os próximos passos
    e o formulário de contato.

    A extração pagou na hora: as correções de alvo de toque de 44px tinham
    sido feitas só na página "Como trabalhamos", e a home tinha ficado com
    `.nav-toggle` de 8px de padding, link de menu sem altura mínima e ícone de
    rede social de 38px. Não era decisão, era cópia envelhecendo sozinha —
    agora o casco é um só e a correção vale nas duas.
--}}
@extends('layouts.site')

@section('titulo', 'UrbisDev — Software que resolve problemas reais')
@section('descricao', 'Soluções digitais sob medida: SaaS, APIs, automações, dashboards e mobile. Sites e sistemas para imobiliárias, blogs e conteúdo.')

@push('head')
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
@endpush

@push('estilos')
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
        .district .card-icon { color: var(--dc, var(--amber-500)); }
        .chip { display: inline-flex; align-items: center; gap: 7px; font-family: var(--font-mono); font-size: .7rem; letter-spacing: .1em; text-transform: uppercase; border: 1px solid var(--line); border-radius: 999px; padding: 5px 12px; color: var(--text-mid); }
        .chip::before { content: ""; width: 6px; height: 6px; border-radius: 50%; background: var(--ok); }
        .chip--dev::before { background: var(--warn); }
        .chip--plan::before { background: var(--d-urb); }
        .chip--dream::before { background: var(--text-low); }

        /* ---------- roadmap (próximos passos) ---------- */
        .roadmap { display: grid; gap: 20px; }
        .roadmap-card { position: relative; border: 1px dashed var(--line); border-radius: var(--r-card); padding: clamp(28px, 4vw, 44px); display: flex; flex-wrap: wrap; gap: 24px; align-items: center; justify-content: space-between; overflow: hidden; }
        .roadmap-card::before { content: ""; position: absolute; top: 0; left: 24px; right: 24px; height: 3px; border-radius: 0 0 3px 3px; background: var(--dc, var(--line)); opacity: .7; }
        .roadmap-card h3 { font-family: var(--font-display); font-size: 1.4rem; display: flex; align-items: center; gap: 12px; margin-top: 12px; }
        .roadmap-card h3 svg { color: var(--dc, var(--text-low)); flex: none; }
        .roadmap-card p { max-width: 52ch; margin-top: 6px; font-size: .98rem; }

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
        form { display: grid; gap: 18px; }
        .field { display: grid; gap: 7px; }
        .field label { font-size: .9rem; font-weight: 600; color: var(--text-hi); }
        /* `--border-input`, não `--line`: ver o comentário do token. A borda
           tênue do card não serve para dizer onde se digita. */
        .field input, .field textarea { background: var(--ink-900); border: 1px solid var(--border-input); border-radius: var(--r-btn); padding: 12px 14px; color: var(--text-hi); font: inherit; font-size: 1rem; transition: border-color .16s ease-out; width: 100%; }
        .field input::placeholder, .field textarea::placeholder { color: var(--text-low); }
        .field input:focus, .field textarea:focus { outline: none; border-color: var(--amber-500); }
        .field textarea { min-height: 130px; resize: vertical; }
        .field-row { display: grid; gap: 18px; }
        @media (min-width: 560px) { .field-row { grid-template-columns: 1fr 1fr; } }
        .field-error { color: var(--error); font-size: .82rem; }
        .consent { display: flex; gap: 10px; align-items: center; min-height: 44px; justify-content: flex-start; font-size: .88rem; color: var(--text-low); text-align: left; }
        /* A caixinha desenhada tem 20px porque 44px de checkbox fica grotesco
           no meio do texto — mas a ÁREA que responde ao toque tem 44px, pelo
           `::before` invisível. O alvo é o que a WCAG mede, não o desenho. */
        .consent input[type="checkbox"] { position: relative; width: 20px; height: 20px; flex: none; padding: 0; accent-color: var(--accent); cursor: pointer; }
        .consent input[type="checkbox"]::before { content: ""; position: absolute; inset: -12px; }
        .consent label { display: flex; align-items: center; min-height: 44px; font-weight: 400; font-size: .88rem; color: var(--fg-subtle); cursor: pointer; }
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
                <span class="eyebrow">SaaS · APIs · Automação · Dashboards · Mobile</span>
                <h1>Software que resolve <em>problemas reais</em>.</h1>
                <p>Desenvolvemos SaaS, APIs, automações, dashboards e aplicativos mobile sob medida para o seu negócio.</p>
                <div class="hero-ctas">
                    <a class="btn btn-primary" href="#contato">Fale Conosco
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="m12 5 7 7-7 7"/></svg>
                    </a>
                    <a class="btn btn-ghost" href="#fazemos">O que fazemos</a>
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
                <div class="districts">
                    <div class="district reveal" style="--dc:var(--d-urb)">
                        <span class="tag">Frente · <b>Imobiliário</b></span>
                        <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 21v-4h6v4"/><path d="M9 10h.01"/><path d="M15 10h.01"/><path d="M9 14h.01"/><path d="M15 14h.01"/></svg>
                        <h3>Imobiliárias</h3>
                        <p>Sites institucionais e sistemas de gestão imobiliária. Portais de imóveis com busca avançada, integração com portais e CRM próprio.</p>
                    </div>
                    <div class="district reveal" style="--dc:var(--amber-500)">
                        <span class="tag">Frente · <b>Conteúdo</b></span>
                        <svg class="card-icon" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z"/></svg>
                        <h3>Blogs &amp; Conteúdo</h3>
                        <p>Blogs corporativos com SEO otimizado, gestão de conteúdo simplificada e design moderno para engajar seu público.</p>
                    </div>
                </div>
            </div>
        </section>

        {{-- Próximos passos --}}
        <section id="proximos">
            <div class="container">
                <div class="section-head reveal">
                    <span class="eyebrow">Próximos passos</span>
                    <h2>O que está por vir na UrbisDev.</h2>
                </div>
                <div class="roadmap reveal">
                    <div class="roadmap-card" style="--dc:var(--d-ssb)">
                        <div>
                            <span class="eyebrow">Próxima obra</span>
                            <h3>
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="12 2 2 7 12 12 22 7 12 2"/><polyline points="2 17 12 22 22 17"/><polyline points="2 12 12 17 22 12"/></svg>
                                Plataformas white-label
                            </h3>
                            <p>Plataformas SaaS multi-tenant com a marca do cliente: cada operação com identidade própria e dados isolados, sobre uma base única — prontas para diferentes áreas de negócio.</p>
                        </div>
                        <span class="chip chip--plan" style="align-self:center">Em planejamento</span>
                    </div>
                    <div class="roadmap-card" style="--dc:var(--d-synapse)">
                        <div>
                            <span class="eyebrow">No horizonte</span>
                            <h3>
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="11" x2="10" y2="11"/><line x1="8" y1="9" x2="8" y2="13"/><line x1="15" y1="12" x2="15.01" y2="12"/><line x1="18" y1="10" x2="18.01" y2="10"/><path d="M17.32 5H6.68a4 4 0 0 0-3.978 3.59c-.006.052-.01.101-.017.152C2.604 9.416 2 14.456 2 16a3 3 0 0 0 3 3c1 0 1.5-.5 2-1l1.414-1.414A2 2 0 0 1 9.828 16h4.344a2 2 0 0 1 1.414.586L17 18c.5.5 1 1 2 1a3 3 0 0 0 3-3c0-1.545-.604-6.584-.685-7.258-.007-.05-.011-.1-.017-.151A4 4 0 0 0 17.32 5z"/></svg>
                                Games
                            </h3>
                            <p>Jogos casuais e educativos para web e mobile. Experiências interativas que unem diversão e propósito.</p>
                        </div>
                        <span class="chip chip--dream" style="align-self:center">O sonho</span>
                    </div>
                </div>
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
                        <textarea id="message" name="message" required minlength="50" maxlength="5000" placeholder="Conte-nos sobre seu projeto (mínimo 50 caracteres)...">{{ old('message') }}</textarea>
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

                    <div class="field" style="justify-items:center">
                        <div class="cf-turnstile" data-sitekey="{{ config('services.turnstile.site_key') }}" data-theme="dark"></div>
                        @error('turnstile_token') <span class="field-error" style="text-align:center">{{ $message }}</span> @enderror
                    </div>

                    <input type="hidden" name="turnstile_token" id="turnstile-token">

                    <div>
                        <button type="submit" class="btn btn-primary">Enviar mensagem</button>
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

        // Turnstile: copia o token pro campo hidden no submit, e trava o botão.
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
            var token = document.querySelector('[name="cf-turnstile-response"]');
            if (token) {
                document.getElementById('turnstile-token').value = token.value;
            }

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
