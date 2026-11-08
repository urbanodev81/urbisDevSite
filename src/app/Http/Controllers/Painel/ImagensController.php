<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Texto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use UrbanoDev\Midia\Http\ImagensController as DoPacote;
use UrbanoDev\Midia\Models\Midia;
use UrbanoDev\Midia\Models\MidiaUso;

/**
 * A galeria do painel (255, 07/11/2026): as imagens do pacote urbanodev/midia
 * e, quando a tela não pede um perfil só, as ilustrações dos guias em
 * `public/img/guias` (decisão dele: a galeria do editor continua oferecendo).
 *
 * Mesmo formato da rota do pacote, para os componentes Vue dele lerem: item
 * de guia vem sem `uuid` (não é do pacote) e com os textos onde já aparece.
 */
class ImagensController extends Controller
{
    private const MAXIMO = 200;

    public function index(Request $request): JsonResponse
    {
        $perfil = $request->string('perfil')->toString();

        $imagens = Midia::query()->daGaleria()
            ->when($perfil !== '', fn ($q) => $q->where('perfil', $perfil))
            ->with('usos.usuario')
            ->latest('id')
            ->limit(self::MAXIMO)
            ->get()
            ->map(fn (Midia $m) => DoPacote::paraTela($m) + [
                'usos' => $m->usos->map(fn (MidiaUso $u) => $u->rotulo())->unique()->values()->all(),
                'selo' => $m->perfil === 'capa' ? 'capa' : null,
            ])
            ->all();

        if ($perfil === '') {
            $imagens = [...$imagens, ...$this->guias()];
        }

        return response()->json(['imagens' => $imagens]);
    }

    /** @return list<array<string, mixed>> */
    private function guias(): array
    {
        // Sem GLOB_BRACE: o PHP do Alpine (musl) não tem.
        $arquivos = preg_grep('~\.(jpe?g|png|webp)$~i', glob(public_path('img/guias/*')) ?: []);
        if (! $arquivos) {
            return [];
        }

        // Onde cada ilustração já aparece (capa ou dentro do texto) e a última descrição dada.
        $usos = [];
        Texto::withTrashed()->get(['titulo', 'imagem', 'imagem_alt', 'corpo_html'])->each(function (Texto $t) use (&$usos) {
            if ($t->imagem) {
                $usos[$t->imagem]['textos'][] = $t->titulo.' (capa)';
                $usos[$t->imagem]['descricao'] = $t->imagem_alt ?: ($usos[$t->imagem]['descricao'] ?? null);
            }
            preg_match_all('~<img\b[^>]*>~i', (string) $t->corpo_html, $tags);
            foreach ($tags[0] as $tag) {
                if (preg_match('~\ssrc="([^"]+)"~', $tag, $src) === 1) {
                    $url = html_entity_decode($src[1]);
                    $usos[$url]['textos'][] = $t->titulo;
                    if (preg_match('~\salt="([^"]+)"~', $tag, $alt) === 1) {
                        $usos[$url]['descricao'] = html_entity_decode($alt[1]);
                    }
                }
            }
        });

        return collect($arquivos)
            ->sortByDesc(fn (string $a) => filemtime($a) ?: 0)
            ->map(function (string $arquivo) use ($usos) {
                $url = '/img/guias/'.basename($arquivo);
                [$largura, $altura] = @getimagesize($arquivo) ?: [null, null];

                return [
                    'uuid' => null,
                    'url' => $url,
                    'nome' => basename($arquivo),
                    'descricao' => $usos[$url]['descricao'] ?? null,
                    'perfil' => 'guia',
                    'largura' => $largura,
                    'altura' => $altura,
                    'usos' => array_values(array_unique($usos[$url]['textos'] ?? [])),
                    'selo' => 'guia',
                ];
            })
            ->values()
            ->all();
    }
}
