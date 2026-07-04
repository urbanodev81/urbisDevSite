<!DOCTYPE html>
<html lang="pt-BR" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UrbisDev — Software que resolve problemas reais</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg: #0f172a;
            --card: #1e293b;
            --border: #334155;
            --primary: #6366f1;
            --grad-from: #818cf8;
            --grad-to: #c084fc;
            --text: #e2e8f0;
            --text-muted: #94a3b8;
        }

        html { scroll-behavior: smooth; }

        body {
            font-family: system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
            background: var(--bg);
            color: var(--text);
            line-height: 1.6;
            min-height: 100vh;
        }

        .container { max-width: 1100px; margin: 0 auto; padding: 0 1.25rem; }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, var(--grad-from), var(--grad-to));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Hero */
        .hero {
            text-align: center;
            padding: 5rem 1rem 4rem;
        }
        .hero-logo {
            width: 120px;
            height: 120px;
            border-radius: 24px;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, var(--grad-from), var(--grad-to));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 900;
            color: #fff;
        }
        .hero h1 { font-size: 2.5rem; margin-bottom: 0.75rem; }
        .hero .tagline {
            font-size: 1.15rem;
            color: var(--text-muted);
            max-width: 560px;
            margin: 0 auto 1.25rem;
        }
        .hero .cta {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.75rem 1.75rem;
            background: var(--primary);
            color: #fff;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 2rem;
            transition: opacity 0.2s;
        }
        .hero .cta:hover { opacity: 0.85; }
        .badges {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            justify-content: center;
        }
        .badge {
            background: var(--card);
            border: 1px solid var(--border);
            padding: 0.35rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            color: var(--text-muted);
        }

        /* Sections */
        .section { padding: 4rem 0; }
        .section-title {
            font-size: 2rem;
            text-align: center;
            margin-bottom: 0.5rem;
        }
        .section-subtitle {
            text-align: center;
            color: var(--text-muted);
            margin-bottom: 3rem;
        }

        /* Cards grid */
        .cards-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1.5rem;
        }
        @media (min-width: 640px) {
            .cards-grid { grid-template-columns: repeat(2, 1fr); }
        }

        /* Card */
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            transition: border-color 0.2s, transform 0.2s;
        }
        .card:hover { border-color: var(--primary); transform: translateY(-2px); }
        .card-icon { font-size: 2rem; margin-bottom: 1rem; }
        .card h3 { font-size: 1.25rem; margin-bottom: 0.5rem; }
        .card p { color: var(--text-muted); font-size: 0.95rem; }

        .card-muted { opacity: 0.5; pointer-events: none; }

        /* Contact form */
        .contact-form {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 12px;
            padding: 2rem;
            max-width: 640px;
            margin: 0 auto;
        }
        .form-group { margin-bottom: 1.25rem; }
        .form-group label {
            display: block;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
            color: var(--text-muted);
        }
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: var(--bg);
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--text);
            font-size: 1rem;
            font-family: inherit;
            transition: border-color 0.2s;
        }
        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
        }
        .form-group textarea { resize: vertical; min-height: 120px; }

        .consent { display: flex; align-items: flex-start; gap: 0.5rem; font-size: 0.85rem; color: var(--text-muted); }
        .consent input { width: auto; margin-top: 0.2rem; }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.85rem 2rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .btn:hover { opacity: 0.9; }
        .btn:disabled { opacity: 0.5; cursor: not-allowed; }

        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
        }
        .alert-success { background: #064e3b; border: 1px solid #065f46; color: #6ee7b7; }
        .alert-error { background: #7f1d1d; border: 1px solid #991b1b; color: #fca5a5; }

        /* Modal */
        .modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 9999;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 16px;
            padding: 2.5rem 2rem;
            max-width: 440px;
            width: 90%;
            text-align: center;
            transform: translateY(20px);
            transition: transform 0.3s;
        }
        .modal-overlay.active .modal { transform: translateY(0); }
        .modal-icon {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.25rem;
        }
        .modal-icon.success { background: #064e3b; color: #6ee7b7; }
        .modal-icon.error { background: #7f1d1d; color: #fca5a5; }
        .modal h3 { font-size: 1.25rem; margin-bottom: 0.5rem; }
        .modal p { color: var(--text-muted); font-size: 0.95rem; margin-bottom: 1.5rem; line-height: 1.5; }
        .modal .btn-close {
            padding: 0.7rem 2rem;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
            transition: opacity 0.2s;
        }
        .modal .btn-close:hover { opacity: 0.85; }

        .char-count { font-size: 0.75rem; color: var(--text-muted); text-align: right; margin-top: 0.25rem; }

        /* Footer */
        .footer {
            padding: 4rem 1rem 3rem;
            color: var(--text-muted);
            font-size: 0.9rem;
            border-top: 1px solid var(--border);
            margin-top: 4rem;
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
            text-align: center;
        }
        @media (min-width: 640px) {
            .footer-grid { grid-template-columns: 2fr 1fr 1fr; text-align: left; }
        }
        .footer-brand .footer-logo {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }
        .footer-brand p { font-size: 0.85rem; line-height: 1.5; }
        .footer h4 {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text);
            margin-bottom: 0.75rem;
        }
        .footer ul { list-style: none; }
        .footer ul li { margin-bottom: 0.4rem; }
        .footer a { color: var(--text-muted); text-decoration: none; transition: color 0.2s; }
        .footer a:hover { color: var(--primary); }
        .footer-bottom {
            text-align: center;
            padding-top: 2.5rem;
            margin-top: 2.5rem;
            border-top: 1px solid var(--border);
            font-size: 0.8rem;
        }

        /* Back to top */
        .back-to-top {
            position: fixed;
            bottom: 1.5rem;
            right: 1.5rem;
            z-index: 9999;
            width: 44px;
            height: 44px;
            background: var(--primary);
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 1.25rem;
            cursor: pointer;
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 0.3s, transform 0.3s;
            pointer-events: none;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .back-to-top.visible { opacity: 1; transform: translateY(0); pointer-events: auto; }
        .back-to-top:hover { opacity: 0.9; }
    </style>
    <script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
</head>
<body>
    {{-- Hero --}}
    <header class="hero" id="top">
        <a href="#top" class="hero-logo">UD</a>
        <h1 class="gradient-text">Urbis<span style="color:var(--text)">Dev</span></h1>
        <p class="tagline">Software que resolve problemas reais</p>
        <a href="#contato" class="cta">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
            Fale Conosco
        </a>
        <div class="badges">
            <span class="badge">SaaS</span>
            <span class="badge">APIs</span>
            <span class="badge">Automação</span>
            <span class="badge">Dashboards</span>
            <span class="badge">Mobile</span>
        </div>
    </header>

    {{-- O que fazemos --}}
    <section class="section" id="fazemos">
        <div class="container">
            <h2 class="section-title gradient-text">O que fazemos</h2>
            <p class="section-subtitle">Soluções digitais sob medida para o seu negócio</p>

            <div class="cards-grid">
                <div class="card">
                    <div class="card-icon">🏢</div>
                    <h3>Imobiliárias</h3>
                    <p>Sites institucionais e sistemas de gestão imobiliária. Portais de imóveis com busca avançada, integração com portais e CRM próprio.</p>
                </div>
                <div class="card">
                    <div class="card-icon">📝</div>
                    <h3>Blogs &amp; Conteúdo</h3>
                    <p>Blogs corporativos com SEO otimizado, gestão de conteúdo simplificada e design moderno para engajar seu público.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Próximos passos --}}
    <section class="section" id="proximos">
        <div class="container">
            <h2 class="section-title gradient-text">Próximos passos</h2>
            <p class="section-subtitle">O que está por vir na UrbisDev</p>

            <div class="cards-grid">
                <div class="card card-muted">
                    <div class="card-icon">🏛️</div>
                    <h3>Sindicatos</h3>
                    <p>Sistemas de gestão sindical completos: cadastro de associados, cobrança, assembleias, comunicação e relatórios.</p>
                </div>
                <div class="card card-muted">
                    <div class="card-icon">🎮</div>
                    <h3>Games</h3>
                    <p>Jogos casuais e educativos para web e mobile. Experiências interativas que unem diversão e propósito.</p>
                </div>
            </div>
        </div>
    </section>

    {{-- Contato --}}
    <section class="section" id="contato">
        <div class="container">
            <h2 class="section-title gradient-text">Entre em contato</h2>
            <p class="section-subtitle">Tem um projeto em mente? Vamos conversar.</p>

            <form action="/contato" method="POST" class="contact-form">
                @csrf

                <div class="form-group">
                    <label for="name">Nome *</label>
                    <input type="text" id="name" name="name" value="{{ old('name') }}" required maxlength="255">
                    @error('name') <span style="color:#fca5a5;font-size:0.8rem">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="email">E-mail *</label>
                    <input type="email" id="email" name="email" value="{{ old('email') }}" required maxlength="255">
                    @error('email') <span style="color:#fca5a5;font-size:0.8rem">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="phone">Telefone *</label>
                    <input type="tel" id="phone" name="phone" value="{{ old('phone') }}" placeholder="(11) 99999-9999" required maxlength="15">
                    @error('phone') <span style="color:#fca5a5;font-size:0.8rem">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <label for="message">Mensagem *</label>
                    <textarea id="message" name="message" required minlength="50" maxlength="5000" placeholder="Conte-nos sobre seu projeto (mínimo 50 caracteres)...">{{ old('message') }}</textarea>
                    <div class="char-count"><span id="charCount">0</span>/5000</div>
                    @error('message') <span style="color:#fca5a5;font-size:0.8rem">{{ $message }}</span> @enderror
                </div>

                <div class="form-group">
                    <div class="consent">
                        <input type="checkbox" id="consent" name="consent" value="1" required>
                        <label for="consent">Autorizo o armazenamento dos meus dados para fins de contato, conforme a LGPD.</label>
                    </div>
                    @error('consent') <span style="color:#fca5a5;font-size:0.8rem">{{ $message }}</span> @enderror
                </div>

                <div class="form-group" style="display:flex;justify-content:center">
                    <div class="cf-turnstile" data-sitekey="0x4AAAAAADvIu0VwIqcVifXe" data-theme="dark"></div>
                </div>
                @error('turnstile_token')
                    <div style="color:#fca5a5;font-size:0.8rem;text-align:center;margin-bottom:1rem">{{ $message }}</div>
                @enderror

                <input type="hidden" name="turnstile_token" id="turnstile-token">
                <script>
                    document.querySelector('form').addEventListener('submit', function(e) {
                        const token = document.querySelector('[name="cf-turnstile-response"]');
                        if (token) {
                            document.getElementById('turnstile-token').value = token.value;
                        }
                    });
                </script>

                <div style="text-align:center">
                    <button type="submit" class="btn">Enviar mensagem</button>
                </div>
            </form>
        </div>
    </section>

    {{-- Footer --}}
    <footer class="footer">
        <div class="container">
            <div class="footer-grid">
                <div class="footer-brand">
                    <div class="footer-logo gradient-text">UrbisDev</div>
                    <p>Software que resolve problemas reais. Desenvolvemos SaaS, APIs, automações, dashboards e aplicativos mobile sob medida para o seu negócio.</p>
                </div>
                <div>
                    <h4>Links</h4>
                    <ul>
                        <li><a href="#top">Início</a></li>
                        <li><a href="#fazemos">O que fazemos</a></li>
                        <li><a href="#proximos">Próximos passos</a></li>
                        <li><a href="#contato">Fale Conosco</a></li>
                    </ul>
                </div>
                <div>
                    <h4>Contato</h4>
                    <ul>
                        <li><a href="mailto:contato@urbisdev.tech">contato@urbisdev.tech</a></li>
                        <li><a href="https://urbisdev.tech">urbisdev.tech</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; {{ date('Y') }} UrbisDev. Todos os direitos reservados.</p>
            </div>
        </div>
    </footer>

    {{-- Modal feedback --}}
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal">
            <div class="modal-icon" id="modalIcon"></div>
            <h3 id="modalTitle"></h3>
            <p id="modalText"></p>
            <button class="btn-close" onclick="document.getElementById('modalOverlay').classList.remove('active')">Fechar</button>
        </div>
    </div>

    {{-- Back to top --}}
    <button class="back-to-top" id="backToTop" onclick="window.scrollTo({top:0,behavior:'smooth'})" aria-label="Voltar ao topo">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="18 15 12 9 6 15"/></svg>
    </button>
    <script>
        const btn = document.getElementById('backToTop');
        window.addEventListener('scroll', () => {
            btn.classList.toggle('visible', window.scrollY > 400);
        });

        // Phone mask
        const phone = document.getElementById('phone');
        phone.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.slice(0, 11);
            if (v.length === 0) { e.target.value = ''; return; }
            if (v.length <= 2) e.target.value = '(' + v;
            else if (v.length <= 7) e.target.value = '(' + v.slice(0,2) + ') ' + v.slice(2);
            else e.target.value = '(' + v.slice(0,2) + ') ' + v.slice(2,7) + '-' + v.slice(7);
        });

        // Char counter
        const msg = document.getElementById('message');
        const counter = document.getElementById('charCount');
        msg.addEventListener('input', () => counter.textContent = msg.value.length);
        counter.textContent = msg.value.length;

        // Modal
        const overlay = document.getElementById('modalOverlay');
        const icon = document.getElementById('modalIcon');
        const modalTitle = document.getElementById('modalTitle');
        const modalText = document.getElementById('modalText');

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
</body>
</html>
