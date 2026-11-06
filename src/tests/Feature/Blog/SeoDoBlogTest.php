<?php

declare(strict_types=1);

use App\Models\Texto;

/*
 * Acabamento de SEO/GEO do blog (auditoria de 06/11/2026):
 * - o texto é artigo (og:type), assinado (autor padrão quando o painel não diz)
 *   e com a trilha Início › Blog › texto;
 * - a marca entra no título da aba de quem não a traz;
 * - tag e mês do blog ficam fora do índice e do sitemap; o sitemap diz a data.
 */

beforeEach(function () {
    $this->pasta = sys_get_temp_dir().'/blog-seo-'.uniqid();
    mkdir($this->pasta);
    config(['blog.pasta' => $this->pasta, 'blog.autor_padrao' => 'Autora Padrão']);

    Texto::create([
        'slug' => 'texto-de-teste',
        'titulo' => 'Texto de teste',
        'descricao' => 'Descrição do texto de teste.',
        'data' => '2026-11-01',
        'atualizado' => '2026-11-03',
        'rascunho' => false,
        'tags' => ['docker'],
        'corpo_html' => '<p>Corpo.</p>',
    ]);
});

afterEach(function () {
    array_map('unlink', glob($this->pasta.'/*.md') ?: []);
    @rmdir($this->pasta);
});

it('serve o texto como artigo assinado, com a marca e a trilha pelo Blog', function () {
    $html = $this->get('/blog/texto-de-teste')->assertOk()->getContent();

    expect($html)
        ->toContain('<title>Texto de teste — UrbisDev</title>')
        ->toContain('<meta property="og:type" content="article">')
        ->toContain('"author":[{"@type":"Person","name":"Autora Padrão"}]')
        ->toContain('"position":2,"name":"Blog"');
});

it('tira tag e mês do índice, mas não o texto', function () {
    expect($this->get('/blog/tag/docker')->getContent())->toContain('<meta name="robots" content="noindex, follow">');
    expect($this->get('/blog/2026/11')->getContent())->toContain('<meta name="robots" content="noindex, follow">');
    expect($this->get('/blog/texto-de-teste')->getContent())->toContain('content="index, follow');
});

it('deixa tag e mês fora do sitemap e diz a data do texto', function () {
    $xml = $this->get('/sitemap.xml')->assertOk()->getContent();

    expect($xml)
        ->toContain('/blog/texto-de-teste</loc><lastmod>2026-11-03</lastmod>')
        ->not->toContain('/blog/tag/')
        ->not->toContain('/blog/2026');
});

it('não duplica a marca no título de quem já a traz', function () {
    expect($this->get('/')->getContent())->toContain('<title>UrbisDev — Software que resolve problemas reais</title>');
});
