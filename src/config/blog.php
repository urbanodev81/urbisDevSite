<?php

return [
    'prefixo' => 'guias',
    'nome_rota' => 'guias',
    'middleware' => ['web'],
    'pasta' => resource_path('blog'),
    'titulo' => 'Guias',
    'introducao' => 'Respostas diretas para quem está decidindo sobre software para o próprio negócio.',
    'por_pagina' => 12,
    'acao_texto' => 'Conversar sobre o meu caso',
    'acao_url' => '/?guia={slug}#contato',
    'layout' => 'layouts.site',
    'secoes' => ['titulo' => 'titulo', 'descricao' => 'descricao', 'conteudo' => 'conteudo', 'imagem' => 'og_imagem'],
    'pilha_cabecalho' => 'estilos',
];
