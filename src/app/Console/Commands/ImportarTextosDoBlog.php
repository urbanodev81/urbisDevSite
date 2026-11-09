<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Blog\SaneadorDeTexto;
use App\Models\Texto;
use Illuminate\Console\Command;
use UrbanoDev\Blog\FonteDeArquivos;
use UrbanoDev\Blog\Post;

/**
 * Traz os `.md` de `resources/blog` para o banco (bloco 3a da 246; decisão do
 * Ricardo de 04/11/2026: "importar e manter .md").
 *
 * Sem `--executar` só mostra o que faria. Slug que já existe no banco (mesmo
 * arquivado) é pulado: nada é sobrescrito. O `.md` continua na pasta, porque é a
 * reserva se o banco cair e é o caminho pelo GitHub. Depois de importar, quem
 * vale é o banco, e editar o `.md` não muda mais o site (o painel avisa).
 *
 * O Markdown vira HTML pelo mesmo conversor do pacote (HTML cru removido) e
 * passa pelo `SaneadorDeTexto` como qualquer texto do painel.
 *
 * `--atualizar=<slug>` (08/11/2026) é a exceção, para revisão feita no `.md`
 * de um texto JÁ importado: reescreve título, descrição e corpo do banco a
 * partir do arquivo. Nasceu porque três revisões foram para o `.md` e o site
 * seguiu mostrando o banco. Recusa texto que alguém editou no painel depois da
 * importação (a trilha diz), para não apagar trabalho de ninguém, salvo com
 * `--forcar`. A gravação entra na trilha como qualquer alteração. O caminho de
 * sempre continua sendo editar no painel.
 */
class ImportarTextosDoBlog extends Command
{
    protected $signature = 'blog:importar
        {--executar : grava de verdade; sem isto, só mostra}
        {--atualizar=* : slug já importado que deve ser reescrito a partir do .md}
        {--forcar : com --atualizar, reescreve mesmo se foi editado no painel}';

    protected $description = 'Copia os textos .md do blog para o banco (o painel passa a editá-los).';

    public function handle(FonteDeArquivos $arquivos, SaneadorDeTexto $saneador): int
    {
        $lido = $arquivos->ler();
        foreach ($lido['erros'] as $arquivo => $erros) {
            $this->warn("{$arquivo}: fora do ar e fora da importação (".implode('; ', $erros).')');
        }

        $executar = (bool) $this->option('executar');

        $slugs = array_values(array_filter(array_map('trim', explode(',', implode(',', (array) $this->option('atualizar'))))));
        if ($slugs !== []) {
            return $this->atualizar($lido['posts'], $slugs, $executar, $saneador);
        }

        $novos = 0;

        foreach ($lido['posts'] as $post) {
            /** @var Post $post */
            if (Texto::withTrashed()->where('slug', $post->slug)->exists()) {
                $this->line("  = {$post->slug}: já está no banco, pulado");

                continue;
            }
            $novos++;
            $this->line("  + {$post->slug}: {$post->titulo} (".($post->rascunho ? 'rascunho' : $post->data->format('d/m/Y')).')');

            if ($executar) {
                Texto::create([
                    'slug' => $post->slug,
                    'titulo' => $post->titulo,
                    'descricao' => $post->descricao,
                    'corpo_html' => $saneador->limpar($post->html()),
                    'data' => $post->data,
                    'atualizado' => $post->atualizado,
                    'rascunho' => $post->rascunho,
                    'autor' => $post->autor,
                    'secao' => $post->secao,
                    'tags' => $post->tags === [] ? null : $post->tags,
                    'imagem' => $post->imagem,
                    'imagem_alt' => $post->imagemAlt,
                    'imagem_credito' => $post->imagemCredito,
                    'acao_texto' => $post->acaoTexto,
                    'acao_url' => $post->acaoUrl,
                    'parceiro' => $post->parceiro,
                    'parceiro_logo' => $post->parceiroLogo,
                    'parceiro_url' => $post->parceiroUrl,
                    'origem' => 'arquivo',
                ]);
            }
        }

        $this->newLine();
        $this->info($executar
            ? "{$novos} texto(s) importado(s). Os .md seguem na pasta como reserva."
            : "{$novos} texto(s) seriam importados. Rode com --executar para gravar.");

        return self::SUCCESS;
    }

    /**
     * @param  list<Post>  $posts
     * @param  list<string>  $slugs
     */
    private function atualizar(array $posts, array $slugs, bool $executar, SaneadorDeTexto $saneador): int
    {
        $porSlug = [];
        foreach ($posts as $post) {
            $porSlug[$post->slug] = $post;
        }

        $falhou = false;
        $gravados = 0;
        foreach ($slugs as $slug) {
            $post = $porSlug[$slug] ?? null;
            if ($post === null) {
                $this->error("  ! {$slug}: não há .md válido com esse nome");
                $falhou = true;

                continue;
            }
            $texto = Texto::query()->where('slug', $slug)->first();
            if ($texto === null) {
                $this->error("  ! {$slug}: não está no banco (ou está arquivado) — use blog:importar sem --atualizar");
                $falhou = true;

                continue;
            }
            $editadoNoPainel = $texto->auditorias()->where('evento', 'alterado')->whereNotNull('user_id')->latest('id')->first();
            if ($editadoNoPainel !== null && ! $this->option('forcar')) {
                $this->error("  ! {$slug}: editado no painel em {$editadoNoPainel->ocorreu_em->format('d/m/Y H:i')} — revise por lá, ou use --forcar para apagar essa edição");
                $falhou = true;

                continue;
            }

            $novo = [
                'titulo' => $post->titulo,
                'descricao' => $post->descricao,
                'corpo_html' => $saneador->limpar($post->html()),
            ];
            $mudam = array_keys(array_filter($novo, fn ($valor, $campo) => $texto->{$campo} !== $valor, ARRAY_FILTER_USE_BOTH));
            if ($mudam === []) {
                $this->line("  = {$slug}: o banco já está igual ao .md");

                continue;
            }
            $this->line("  ~ {$slug}: muda ".implode(', ', $mudam));
            if ($executar) {
                $texto->update($novo);
                $gravados++;
            }
        }

        $this->newLine();
        $this->info($executar
            ? "{$gravados} texto(s) reescrito(s) a partir do .md."
            : 'Nada gravado. Rode com --executar para reescrever.');

        return $falhou ? self::FAILURE : self::SUCCESS;
    }
}
