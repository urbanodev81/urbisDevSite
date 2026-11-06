<?php

return [
    'prefixo' => 'blog',
    'nome_rota' => 'blog',
    'middleware' => ['web'],
    // Banco antes dos .md (246, bloco 3a): o mesmo slug fica com o do painel;
    // banco fora, os .md seguem no ar sozinhos.
    'fontes' => [App\Blog\FonteDoBanco::class, UrbanoDev\Blog\FonteDeArquivos::class],
    'pasta' => resource_path('blog'),
    'titulo' => 'Blog',
    // Quem assina o texto do painel que não diz o autor (byline e JSON-LD).
    'autor_padrao' => env('BLOG_AUTOR_PADRAO', 'Ricardo Urbano'),
    'introducao' => 'Respostas diretas para quem está decidindo sobre software para o próprio negócio.',
    'por_pagina' => 12,
    'acao_texto' => 'Conversar sobre o meu caso',
    'acao_url' => '/?texto={slug}#contato',
    // Leitores (bloco 3b da 246): "ajudou?" e comentários no fim do texto, mais
    // lidos na entrada do blog e o "Fale com a gente" do WhatsApp em toda página.
    'encaixes' => [
        'fim_do_texto' => ['blog.leitores'],
        'fim_do_indice' => ['blog.mais-lidos'],
        'pagina' => ['blog.whatsapp', 'blog.imagens'],
    ],
    'layout' => 'layouts.site',
    'secoes' => ['titulo' => 'titulo', 'descricao' => 'descricao', 'conteudo' => 'conteudo', 'imagem' => 'og_imagem', 'robots' => 'robots'],
    'pilha_cabecalho' => 'estilos',
];
