{{--
    Tamanho e posição da imagem dentro do texto (05/11/2026). O editor grava
    `data-tamanho` (pequena|media) e `data-alinhar` (esquerda|centro|direita);
    o `SaneadorDeTexto` só deixa passar esses valores. Sem atributo = inteira,
    como sempre foi. Os números são os mesmos do editor
    (`EditorDeTexto.vue`): mudou um, mude o outro.

    No celular a imagem nunca fica com texto ao lado: em 390 px uma coluna de
    texto ao lado de uma foto vira três palavras por linha.

    Ampliar ao clicar (06/11/2026): só a foto marcada no editor
    (`data-ampliar="sim"`). Abre num <dialog> nativo — Esc, clique fora ou o
    X fecham, e o foco volta para a foto. Ouvinte em JS, não `onclick=`: a CSP
    barra atributo de evento.
--}}
@push('estilos')
    <style>
        .blog-texto img[data-tamanho="pequena"] { width: 33%; }
        .blog-texto img[data-tamanho="media"] { width: 50%; }
        .blog-texto img[data-alinhar="centro"] { display: block; margin-inline: auto; }
        .blog-texto img[data-alinhar="esquerda"] { float: left; margin: .35rem 1.5rem 1rem 0; }
        .blog-texto img[data-alinhar="direita"] { float: right; margin: .35rem 0 1rem 1.5rem; }
        .blog-texto::after { content: ''; display: table; clear: both; }
        .blog-texto h2, .blog-texto h3, .blog-texto hr, .blog-texto pre, .blog-texto figure { clear: both; }
        .blog-texto img[data-ampliar="sim"] { cursor: zoom-in; }
        .blog-texto img[data-ampliar="sim"]:focus-visible { outline: 3px solid currentColor; outline-offset: 3px; }
        .foto-ampliada { position: fixed; inset: 0; margin: auto; width: fit-content; height: fit-content; padding: 0; border: 0; background: transparent; max-width: 96vw; max-height: 94vh; overflow: visible; }
        .foto-ampliada::backdrop { background: rgba(5, 8, 15, .88); }
        .foto-ampliada img { display: block; max-width: 96vw; max-height: 88vh; width: auto; height: auto; border-radius: .5rem; cursor: zoom-out; }
        .foto-ampliada p { margin: .6rem 0 0; color: #e8ecf3; font-size: .95rem; text-align: center; }
        .foto-ampliada button { position: fixed; top: .75rem; right: .75rem; width: 2.75rem; height: 2.75rem; border: 0; border-radius: 999px;
            background: rgba(255, 255, 255, .14); color: #fff; font-size: 1.6rem; line-height: 1; cursor: pointer; }
        .foto-ampliada button:focus-visible { outline: 3px solid #fff; outline-offset: 2px; }
        @media (max-width: 600px) {
            .blog-texto img[data-alinhar="esquerda"], .blog-texto img[data-alinhar="direita"] { float: none; display: block; margin: 1rem auto; }
            .blog-texto img[data-tamanho="pequena"] { width: 60%; }
            .blog-texto img[data-tamanho="media"] { width: 100%; }
        }
    </style>
@endpush

@push('scripts')
    <dialog class="foto-ampliada" id="foto-ampliada" aria-label="Foto ampliada">
        <button type="button" aria-label="Fechar a foto">×</button>
        <img alt="">
        <p hidden></p>
    </dialog>
    <script nonce="{{ $cspNonce ?? '' }}">
        (() => {
            const caixa = document.getElementById('foto-ampliada');
            const fotos = document.querySelectorAll('.blog-texto img[data-ampliar="sim"]');
            if (!caixa || !fotos.length || typeof caixa.showModal !== 'function') return;
            const grande = caixa.querySelector('img');
            const legenda = caixa.querySelector('p');
            let origem = null;
            const abrir = (foto) => {
                origem = foto;
                grande.src = foto.currentSrc || foto.src;
                grande.alt = foto.alt;
                legenda.textContent = foto.alt;
                legenda.hidden = !foto.alt;
                caixa.showModal();
            };
            fotos.forEach((foto) => {
                foto.tabIndex = 0;
                foto.setAttribute('role', 'button');
                foto.setAttribute('aria-label', 'Ampliar a foto' + (foto.alt ? ': ' + foto.alt : ''));
                foto.addEventListener('click', () => abrir(foto));
                foto.addEventListener('keydown', (e) => {
                    if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); abrir(foto); }
                });
            });
            // Qualquer clique fecha: no X, na foto grande ou no fundo escuro.
            caixa.addEventListener('click', () => caixa.close());
            caixa.addEventListener('close', () => { grande.removeAttribute('src'); origem?.focus(); });
        })();
    </script>
@endpush
