{{--
    Fim de cada texto do blog (bloco 3b da 246, 04/11/2026): "Este texto
    ajudou?" e os comentários. Entra pelo encaixe `fim_do_texto` do pacote
    (config/blog.php) e recebe `$post`.

    Tudo é formulário comum, que funciona sem JS, e volta para a âncora do
    bloco. O único JS é o aviso de leitura (sendBeacon), lá embaixo: é ele que
    separa gente de robô na contagem dos mais lidos.
--}}
@php
    use App\Http\Controllers\Blog\LeitoresController;
    use App\Http\Controllers\Blog\LinkedinController;

    $slug = $post->slug;
    $voto = session('blog.voto');
    $jaVotou = session()->has('blog.votos.'.$slug);
    $comentarios = app(\App\Blog\Leitores::class)->comentariosAprovados($slug);
    $linkedin = session(LeitoresController::SESSAO_LINKEDIN);
    $erros = $errors->getBag('comentario');
    $recebido = session('blog.comentario') === 'recebido';
@endphp

@if (! $linkedin)
    @push('head')
        <script type="module" src="/vendor/altcha/pt-br-3.2.2.js"></script>
        <script type="module" src="/vendor/altcha/altcha-3.2.2.min.js"></script>
    @endpush
@endif

@push('estilos')
    <style>
        @include('partials.formulario-estilos')
        .blog-leitores { margin-top: 2.5rem; display: grid; gap: 2rem; }
        .blog-leitores h2 { font-family: var(--font-display); font-size: 1.35rem; margin: 0 0 .75rem; color: var(--text-hi); }
        .blog-ajudou { border: 1px solid var(--border); border-radius: var(--r-card); padding: 1.25rem 1.5rem; background: var(--surface); }
        .blog-ajudou form { display: flex; flex-wrap: wrap; align-items: center; gap: .75rem; }
        .blog-ajudou p { margin: 0; color: var(--text-hi); font-weight: 600; }
        .blog-ajudou .blog-ajudou-faltou { display: grid; gap: .75rem; width: 100%; }
        @media (max-width: 480px) { .blog-ajudou form > p { flex-basis: 100%; } }
        .blog-ajudou-obrigado { color: var(--fg-muted) !important; font-weight: 400 !important; }
        .blog-comentario { display: grid; grid-template-columns: 44px 1fr; gap: .25rem 1rem; padding: 1.25rem 0; border-top: 1px solid var(--border); }
        .blog-comentario:first-of-type { border-top: 0; }
        .blog-iniciais { width: 44px; height: 44px; border-radius: 50%; display: grid; place-items: center; background: var(--surface-raised); border: 1px solid var(--border); font-family: var(--font-mono); font-size: .85rem; color: var(--fg); grid-row: span 2; }
        .blog-comentario-quem { margin: 0; font-size: .9rem; color: var(--fg-muted); }
        .blog-comentario-quem strong { color: var(--text-hi); }
        .blog-selo { display: inline-flex; align-items: center; gap: .25rem; font-size: .75rem; color: var(--fg-muted); border: 1px solid var(--border); border-radius: 999px; padding: 0 .5rem; margin-left: .25rem; }
        .blog-comentario-corpo { margin: .25rem 0 0; white-space: pre-line; color: var(--fg); }
        .blog-resposta { grid-column: 2; margin-top: .75rem; padding: .75rem 1rem; border-left: 3px solid var(--accent); background: var(--surface); border-radius: 0 var(--r-btn) var(--r-btn) 0; }
        .blog-resposta p { margin: 0; white-space: pre-line; }
        .blog-resposta .blog-comentario-quem { margin-bottom: .25rem; }
        .blog-comentar { border: 1px solid var(--border); border-radius: var(--r-card); padding: 1.5rem; background: var(--surface); display: grid; gap: 1rem; }
        .blog-comentar-titulo { margin: 0 !important; }
        .blog-comentar-nota { margin: 0; font-size: .85rem; color: var(--fg-muted); }
        .blog-comentar-aviso { margin: 0; padding: .75rem 1rem; border-radius: var(--r-btn); border: 1px solid var(--ok); color: var(--fg); }
        .blog-comentar-erro { margin: 0; padding: .75rem 1rem; border-radius: var(--r-btn); border: 1px solid var(--error); color: var(--fg); }
        .blog-linkedin { display: inline-flex; align-items: center; gap: .5rem; text-decoration: none; }
        .blog-linkedin svg { color: var(--marca-linkedin); }
        .blog-comentar-ou { margin: 0; font-size: .85rem; color: var(--fg-subtle); }
        .blog-como { display: flex; flex-wrap: wrap; align-items: center; gap: .5rem 1rem; margin: 0; }
        .blog-como form { display: inline; }
        .blog-como button { background: none; border: 0; padding: .5rem 0; min-height: 44px; color: var(--fg-muted); text-decoration: underline; cursor: pointer; font: inherit; font-size: .9rem; }
    </style>
@endpush

<div class="blog-leitores">
    {{-- "Este texto ajudou?". div e não section: o layout dá espaçamento grande a toda section. --}}
    <div class="blog-ajudou" id="ajudou" role="region" aria-labelledby="ajudou-titulo">
        <h2 id="ajudou-titulo" class="blog-sr">Este texto ajudou?</h2>
        @if ($voto === 'nao')
            <form method="POST" action="{{ url('/blog/'.$slug.'/ajudou') }}">
                @csrf
                <div class="blog-ajudou-faltou field">
                    <label for="faltou">Obrigado por dizer. O que faltou? <span class="blog-ajudou-obrigado">(opcional)</span></label>
                    <textarea id="faltou" name="faltou" maxlength="1000" rows="3" placeholder="Uma dúvida que ficou, um exemplo, um assunto…"></textarea>
                </div>
                <button type="submit" class="btn btn-primary">Enviar</button>
            </form>
        @elseif ($voto === 'sim')
            <p role="status">Que bom! Obrigado por dizer.</p>
        @elseif ($voto === 'faltou')
            <p role="status">Recebido. Obrigado por contar o que faltou: é assim que o próximo texto sai melhor.</p>
        @elseif ($voto === 'erro')
            <p role="status">Não conseguimos guardar a resposta agora. Tente de novo mais tarde.</p>
        @elseif ($jaVotou)
            <p class="blog-ajudou-obrigado">Você já respondeu se este texto ajudou. Obrigado!</p>
        @else
            <form method="POST" action="{{ url('/blog/'.$slug.'/ajudou') }}">
                @csrf
                <p aria-hidden="true">Este texto ajudou?</p>
                <button type="submit" name="util" value="sim" class="btn btn-ghost" aria-label="Sim, este texto ajudou">Sim</button>
                <button type="submit" name="util" value="nao" class="btn btn-ghost" aria-label="Não, este texto não ajudou">Não</button>
            </form>
        @endif
    </div>

    {{-- Comentários. Com o banco fora, o bloco some e o texto continua no ar. --}}
    @if ($comentarios !== null)
        <div id="comentarios" role="region" aria-labelledby="comentarios-titulo">
            <h2 id="comentarios-titulo">Comentários @if ($comentarios->isNotEmpty())({{ $comentarios->count() }})@endif</h2>

            @forelse ($comentarios as $c)
                <article class="blog-comentario" id="comentario-{{ $c->uuid }}">
                    <span class="blog-iniciais" aria-hidden="true">{{ $c->iniciais() }}</span>
                    <p class="blog-comentario-quem">
                        <strong>{{ $c->nome }}</strong>
                        @if ($c->verificadoPeloLinkedin())
                            <span class="blog-selo">verificado pelo LinkedIn</span>
                        @endif
                        · <time datetime="{{ $c->created_at->toDateString() }}">{{ $c->created_at->format('d/m/Y') }}</time>
                    </p>
                    <p class="blog-comentario-corpo">{{ $c->corpo }}</p>
                    @if ($c->resposta)
                        <div class="blog-resposta">
                            <p class="blog-comentario-quem"><strong>urbisDev</strong> respondeu</p>
                            <p>{{ $c->resposta }}</p>
                        </div>
                    @endif
                </article>
            @empty
                <p class="blog-comentar-nota">Ninguém comentou ainda.</p>
            @endforelse

            <div class="blog-comentar" id="comentar">
                <h2 class="blog-comentar-titulo">Deixe o seu comentário</h2>

                @if ($recebido)
                    <p class="blog-comentar-aviso" role="status">Recebemos o seu comentário. Ele aparece aqui depois que a equipe ler.</p>
                @endif
                @foreach (['envio', 'linkedin'] as $campo)
                    @if ($erros->has($campo))
                        <p class="blog-comentar-erro" role="alert">{{ $erros->first($campo) }}</p>
                    @endif
                @endforeach

                @if ($linkedin)
                    <div class="blog-como">
                        <span>Comentando como <strong>{{ $linkedin['nome'] }}</strong> <span class="blog-selo">verificado pelo LinkedIn</span></span>
                        <form method="POST" action="{{ url('/blog/entrar/linkedin/sair') }}">
                            @csrf
                            <input type="hidden" name="volta" value="{{ $slug }}">
                            <button type="submit">Não sou eu</button>
                        </form>
                    </div>
                @elseif (LinkedinController::disponivel())
                    <p class="blog-comentar-nota">
                        <a class="btn btn-ghost blog-linkedin" href="{{ url('/blog/entrar/linkedin').'?volta='.$slug }}">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.46v6.28zM5.34 7.43a2.06 2.06 0 1 1 0-4.13 2.06 2.06 0 0 1 0 4.13zM7.12 20.45H3.56V9h3.56v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg>
                            Comentar com o LinkedIn
                        </a>
                    </p>
                    <p class="blog-comentar-ou">ou com nome e e-mail:</p>
                @endif

                <form method="POST" action="{{ url('/blog/'.$slug.'/comentarios') }}">
                    @csrf
                    @unless ($linkedin)
                        <div class="field-row">
                            <div class="field">
                                <label for="comentario-nome">Nome *</label>
                                <input type="text" id="comentario-nome" name="nome" value="{{ old('nome') }}" required maxlength="120" autocomplete="name">
                                @if ($erros->has('nome')) <span class="field-error">{{ $erros->first('nome') }}</span> @endif
                            </div>
                            <div class="field">
                                <label for="comentario-email">E-mail * <span class="blog-ajudou-obrigado">(não aparece)</span></label>
                                <input type="email" id="comentario-email" name="email" value="{{ old('email') }}" required maxlength="255" autocomplete="email">
                                @if ($erros->has('email')) <span class="field-error">{{ $erros->first('email') }}</span> @endif
                            </div>
                        </div>
                    @endunless
                    <div class="field">
                        <label for="comentario-corpo">Comentário *</label>
                        <textarea id="comentario-corpo" name="corpo" required minlength="10" maxlength="3000">{{ old('corpo') }}</textarea>
                        @if ($erros->has('corpo')) <span class="field-error">{{ $erros->first('corpo') }}</span> @endif
                    </div>
                    <div class="field">
                        <div class="consent">
                            <input type="checkbox" id="comentario-consent" name="consent" value="1" required>
                            <label for="comentario-consent">Autorizo guardar meu nome e e-mail para a moderação do comentário, conforme a LGPD e a <a href="{{ url('/privacidade') }}">política de privacidade</a>. O nome aparece junto do comentário; o e-mail, não.</label>
                        </div>
                        @if ($erros->has('consent')) <span class="field-error">{{ $erros->first('consent') }}</span> @endif
                    </div>
                    @unless ($linkedin)
                        <div class="field captcha">
                            <altcha-widget challenge="/captcha/desafio" name="captcha_token" language="pt-br" auto="onload" hidefooter hidelogo></altcha-widget>
                            @if ($erros->has('captcha_token')) <span class="field-error">{{ $erros->first('captcha_token') }}</span> @endif
                        </div>
                    @endunless
                    <p class="blog-comentar-nota">Todo comentário é lido pela equipe antes de aparecer. Sem links, por favor.</p>
                    <div><button type="submit" class="btn btn-primary">Enviar comentário</button></div>
                </form>
            </div>
        </div>
    @endif
</div>

@push('scripts')
    {{-- Conta a leitura depois de 4 s com a aba à vista. Robô quase nunca roda JS. --}}
    <script nonce="{{ $cspNonce ?? '' }}">
        (function () {
            var url = @json(url('/blog/'.$slug.'/lido')), token = @json(csrf_token()), feito = false, relogio = null;
            function contar() {
                if (feito) return;
                feito = true;
                var dados = new FormData();
                dados.append('_token', token);
                if (navigator.sendBeacon) navigator.sendBeacon(url, dados);
                else fetch(url, { method: 'POST', body: dados, credentials: 'same-origin', keepalive: true });
            }
            function vigiar() {
                clearTimeout(relogio);
                if (document.visibilityState === 'visible') relogio = setTimeout(contar, 4000);
            }
            document.addEventListener('visibilitychange', vigiar);
            vigiar();
        })();
    </script>
@endpush
