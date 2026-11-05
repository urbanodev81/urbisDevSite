{{--
    Tamanho e posição da imagem dentro do texto (05/11/2026). O editor grava
    `data-tamanho` (pequena|media) e `data-alinhar` (esquerda|centro|direita);
    o `SaneadorDeTexto` só deixa passar esses valores. Sem atributo = inteira,
    como sempre foi. Os números são os mesmos do editor
    (`EditorDeTexto.vue`): mudou um, mude o outro.

    No celular a imagem nunca fica com texto ao lado: em 390 px uma coluna de
    texto ao lado de uma foto vira três palavras por linha.
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
        @media (max-width: 600px) {
            .blog-texto img[data-alinhar="esquerda"], .blog-texto img[data-alinhar="direita"] { float: none; display: block; margin: 1rem auto; }
            .blog-texto img[data-tamanho="pequena"] { width: 60%; }
            .blog-texto img[data-tamanho="media"] { width: 100%; }
        }
    </style>
@endpush
