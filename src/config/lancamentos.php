<?php

/*
|--------------------------------------------------------------------------
| Banner de lançamentos da home
|--------------------------------------------------------------------------
|
| Cada entrada é um slide, na ordem em que passam. Só entra o que já foi
| lançado e tem endereço público. Lançou outro? Acrescente aqui, com a captura
| em 1200x750 em `public/lancamentos/`; o banner e os controles se ajustam.
|
| Com uma entrada só, o banner vira uma seção parada, sem controles.
|
*/

return [
    [
        'etiqueta' => 'Nosso produto · No ar',
        'titulo' => 'USI, a plataforma da UrbisDev para imobiliárias.',
        'texto' => 'Site com busca de imóveis, fichas de lançamento e blog, e a gestão por trás, com o acervo, os contatos dos interessados e a marca da imobiliária em tudo.',
        'url' => 'https://usi.urbisdev.tech',
        'chamada' => 'Conhecer o USI',
        'secundario_url' => 'https://usi.urbisdev.tech/planos',
        'secundario' => 'Ver planos e preços',
        'nota' => '30 dias grátis, sem cartão de crédito.',
        'imagem' => '/lancamentos/usi.jpg',
    ],
    [
        'etiqueta' => 'Cliente · No ar',
        'titulo' => 'Imóveis Jacob, um site feito com o USI.',
        'texto' => 'Busca por negócio, tipo, cidade e faixa de preço, ficha de cada imóvel e contato direto com o corretor, tudo ligado ao sistema da imobiliária.',
        'url' => 'https://imoveisjacob.com.br',
        'chamada' => 'Visitar o site',
        'secundario_url' => 'https://usi.urbisdev.tech',
        'secundario' => 'Conhecer o USI',
        'nota' => null,
        'imagem' => '/lancamentos/imoveis-jacob.jpg',
    ],
];
