<?php

return [
    'prefixo' => 'blog',
    'nome_rota' => 'blog',
    'middleware' => ['web'],
    'pasta' => resource_path('blog'),
    'titulo' => 'Blog',
    'introducao' => 'Respostas diretas para quem está decidindo sobre software para o próprio negócio.',
    'por_pagina' => 12,
    'acao_texto' => 'Conversar sobre o meu caso',
    'acao_url' => '/?texto={slug}#contato',
    'layout' => 'layouts.site',
    'secoes' => ['titulo' => 'titulo', 'descricao' => 'descricao', 'conteudo' => 'conteudo', 'imagem' => 'og_imagem', 'robots' => 'robots'],
    'pilha_cabecalho' => 'estilos',
];
