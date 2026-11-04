{{--
    "Fale com a gente" (bloco 3b da 246, 04/11/2026): botão fixo de WhatsApp
    em toda página do blog, com a mensagem pronta citando o texto. Encaixe
    `pagina`; `$post` é null na lista.

    Ele toma o lugar da pilha social flutuante do layout nestas páginas: dois
    botões de WhatsApp no mesmo canto seria um a mais.
--}}
@php
    $mensagem = $post
        ? 'Olá! Li o texto "'.$post->titulo.'" no blog da urbisDev e quero conversar sobre o meu caso.'
        : 'Olá! Vim pelo blog da urbisDev e quero conversar sobre o meu caso.';
@endphp

@push('estilos')
    <style>
        .social-float { display: none !important; }
        .blog .blog-fale, .blog-fale { position: fixed; right: 24px; bottom: 84px; z-index: 998; display: inline-flex; align-items: center; gap: .5rem; min-height: 48px; padding: 0 1.1rem 0 .9rem; border-radius: 999px; background: var(--marca-whatsapp); color: #0b1f12 !important; font-family: var(--font-body); font-weight: 700; font-size: .95rem; text-decoration: none; box-shadow: var(--shadow-flutuante); transition: transform .18s ease-out; }
        .blog-fale:hover { transform: translateY(-2px); }
        .blog-fale:focus-visible { outline: 3px solid var(--accent); outline-offset: 3px; }
        @media (max-width: 480px) { .blog-fale span { position: absolute; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); } .blog-fale { padding: 0; width: 52px; height: 52px; justify-content: center; } }
        @media (prefers-reduced-motion: reduce) { .blog-fale { transition: none; } .blog-fale:hover { transform: none; } }
        @media print { .blog-fale { display: none; } }
    </style>
@endpush

<a class="blog-fale" href="https://wa.me/{{ config('site.whatsapp') }}?text={{ rawurlencode($mensagem) }}" target="_blank" rel="noopener">
    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17.47 14.38c-.3-.15-1.76-.87-2.03-.97-.27-.1-.47-.15-.67.15-.2.3-.77.97-.94 1.17-.17.2-.35.22-.65.07-.3-.15-1.26-.46-2.4-1.48-.89-.79-1.49-1.77-1.66-2.07-.17-.3-.02-.46.13-.61.13-.13.3-.35.45-.52.15-.17.2-.3.3-.5.1-.2.05-.37-.03-.52-.07-.15-.67-1.62-.92-2.22-.24-.58-.49-.5-.67-.51h-.57c-.2 0-.52.07-.79.37-.27.3-1.04 1.02-1.04 2.48s1.07 2.88 1.21 3.08c.15.2 2.1 3.2 5.08 4.49.71.31 1.26.49 1.69.63.71.23 1.36.2 1.87.12.57-.09 1.76-.72 2.01-1.41.25-.7.25-1.29.17-1.41-.07-.13-.27-.2-.57-.35M12.05 21.5h-.01a9.4 9.4 0 0 1-4.79-1.31l-.34-.2-3.56.93.95-3.47-.22-.36a9.4 9.4 0 0 1-1.44-5.01c0-5.2 4.23-9.43 9.44-9.43a9.38 9.38 0 0 1 9.43 9.44c0 5.2-4.24 9.43-9.44 9.43M20.07 3.92A11.27 11.27 0 0 0 12.05.6C5.8.6.7 5.7.7 11.95c0 2 .52 3.95 1.52 5.67L.6 23.4l5.9-1.55a11.33 11.33 0 0 0 5.54 1.41h.01c6.25 0 11.35-5.1 11.35-11.35 0-3.03-1.18-5.88-3.33-8.02"/></svg>
    <span>Fale com a gente</span><span class="blog-sr"> no WhatsApp (abre em nova aba)</span>
</a>
