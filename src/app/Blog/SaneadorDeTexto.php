<?php

declare(strict_types=1);

namespace App\Blog;

use Dom\Element;
use Dom\HTMLDocument;
use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

/**
 * O corpo dos textos do blog (bloco 3a da 246, 04/11/2026).
 *
 * **O editor do painel não é barreira: o servidor é.** Qualquer um com a
 * sessão pode mandar o HTML que quiser direto para a rota. Por isso o corpo
 * passa por aqui ANTES de ir para o banco (`limpar`), numa lista branca curta:
 * o que não está nela some.
 *
 * - imagem só do próprio blog (`/storage/blog/…webp`, o que o painel gerou);
 * - vídeo NUNCA como iframe vindo de fora: o editor guarda
 *   `<figure data-video="youtube|vimeo" data-id="…">` e o id é conferido aqui;
 *   o iframe só nasce na leitura (`paraLeitura`), com endereço montado por nós
 *   (`youtube-nocookie.com`, `player.vimeo.com?dnt=1`), que é o que a CSP
 *   libera em `frame-src`.
 */
class SaneadorDeTexto
{
    public const PROVEDORES = ['youtube' => '/^[A-Za-z0-9_-]{11}$/', 'vimeo' => '/^\d{6,12}$/'];

    /**
     * Imagem que pode ficar no texto: a que o painel subiu (WebP sem EXIF) ou
     * uma das ilustrações dos guias que moram no próprio site (05/11/2026, a
     * galeria deixa reusar as duas). Endereço de fora não entra.
     */
    public const IMAGEM = '~^(/storage/blog/\d{4}/\d{2}/[0-9a-f-]{36}\.webp|/img/guias/[a-z0-9-]+\.(jpe?g|png|webp))$~';

    /** Tamanho e alinhamento da imagem no texto (05/11/2026): só estes valores. */
    public const TAMANHOS = ['pequena', 'media', 'inteira'];

    public const ALINHAMENTOS = ['esquerda', 'centro', 'direita'];

    private HtmlSanitizer $sanitizer;

    public function __construct()
    {
        $config = (new HtmlSanitizerConfig)
            ->allowLinkSchemes(['https', 'http', 'mailto'])
            ->allowRelativeLinks()
            ->allowRelativeMedias()
            ->allowMediaSchemes([])
            ->withMaxInputLength(500_000);

        // Tabela não sai do editor, mas pode vir de um .md importado.
        foreach (['p', 'h2', 'h3', 'h4', 'strong', 'em', 's', 'ul', 'ol', 'li', 'blockquote', 'pre', 'code', 'hr', 'br', 'figcaption', 'table', 'thead', 'tbody', 'tr', 'th', 'td'] as $tag) {
            $config = $config->allowElement($tag);
        }
        $config = $config
            ->allowElement('a', ['href', 'title'])
            ->allowElement('img', ['src', 'alt', 'width', 'height', 'data-tamanho', 'data-alinhar'])
            ->allowElement('figure', ['data-video', 'data-id'])
            // Tag de formatação que não está na lista sai, o texto dentro fica.
            ->blockElement('span')->blockElement('div')->blockElement('u')->blockElement('h1')
            ->blockElement('b')->blockElement('i')->blockElement('font')->blockElement('section');

        $this->sanitizer = new HtmlSanitizer($config);
    }

    /** Para gravar: lista branca + as regras que a lista sozinha não expressa. */
    public function limpar(string $html): string
    {
        $limpo = $this->sanitizer->sanitize($html);
        if (trim($limpo) === '') {
            return '';
        }

        [$doc, $corpo] = $this->abrir($limpo);

        foreach (iterator_to_array($corpo->querySelectorAll('img')) as $img) {
            if (preg_match(self::IMAGEM, (string) $img->getAttribute('src')) !== 1) {
                $img->remove();

                continue;
            }
            foreach (['width', 'height'] as $medida) {
                if ($img->hasAttribute($medida) && preg_match('/^\d{1,4}$/', (string) $img->getAttribute($medida)) !== 1) {
                    $img->removeAttribute($medida);
                }
            }
            foreach (['data-tamanho' => self::TAMANHOS, 'data-alinhar' => self::ALINHAMENTOS] as $atributo => $valores) {
                if ($img->hasAttribute($atributo) && ! in_array($img->getAttribute($atributo), $valores, true)) {
                    $img->removeAttribute($atributo);
                }
            }
        }

        foreach (iterator_to_array($corpo->querySelectorAll('figure')) as $figura) {
            $provedor = $figura->getAttribute('data-video');
            if ($provedor === null && ! $figura->hasAttribute('data-id')) {
                continue;
            }
            $id = (string) $figura->getAttribute('data-id');
            if (! isset(self::PROVEDORES[$provedor]) || preg_match(self::PROVEDORES[$provedor], $id) !== 1) {
                $figura->remove();

                continue;
            }
            // Vídeo é só o cartão (e a legenda, se houver): nada mais dentro.
            foreach (iterator_to_array($figura->childNodes) as $filho) {
                if (! ($filho instanceof Element && $filho->localName === 'figcaption')) {
                    $filho->remove();
                }
            }
        }

        // O editor deixa um parágrafo vazio depois de imagem e vídeo; no fim do texto, sobra.
        return (string) preg_replace('~(?:<p>(?:\s|<br>)*</p>)+$~', '', $corpo->innerHTML);
    }

    /** Para mostrar: o cartão de vídeo vira o iframe sem cookie; imagem carrega quando chega perto. */
    public function paraLeitura(string $html): string
    {
        if (! str_contains($html, '<img') && ! str_contains($html, 'data-video')) {
            return $html;
        }

        [$doc, $corpo] = $this->abrir($html);

        foreach (iterator_to_array($corpo->querySelectorAll('img')) as $img) {
            $img->setAttribute('loading', 'lazy');
            $img->setAttribute('decoding', 'async');
        }

        foreach (iterator_to_array($corpo->querySelectorAll('figure[data-video]')) as $figura) {
            $provedor = (string) $figura->getAttribute('data-video');
            $id = (string) $figura->getAttribute('data-id');
            if (! isset(self::PROVEDORES[$provedor]) || preg_match(self::PROVEDORES[$provedor], $id) !== 1) {
                $figura->remove();

                continue;
            }
            $iframe = $doc->createElement('iframe');
            $iframe->setAttribute('src', $provedor === 'youtube'
                ? 'https://www.youtube-nocookie.com/embed/'.$id
                : 'https://player.vimeo.com/video/'.$id.'?dnt=1');
            $iframe->setAttribute('title', $provedor === 'youtube' ? 'Vídeo do YouTube' : 'Vídeo do Vimeo');
            $iframe->setAttribute('loading', 'lazy');
            $iframe->setAttribute('referrerpolicy', 'strict-origin-when-cross-origin');
            $iframe->setAttribute('allow', 'encrypted-media; picture-in-picture; fullscreen');
            $iframe->setAttribute('allowfullscreen', '');

            $figura->removeAttribute('data-video');
            $figura->removeAttribute('data-id');
            $figura->setAttribute('class', 'blog-video');
            $figura->prepend($iframe);
        }

        return $corpo->innerHTML;
    }

    /** @return array{0: HTMLDocument, 1: Element} */
    private function abrir(string $html): array
    {
        $doc = HTMLDocument::createFromString('<!DOCTYPE html><html><body>'.$html.'</body></html>', LIBXML_NOERROR, 'UTF-8');

        return [$doc, $doc->body];
    }
}
