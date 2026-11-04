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
 */
class ImportarTextosDoBlog extends Command
{
    protected $signature = 'blog:importar {--executar : grava de verdade; sem isto, só mostra}';

    protected $description = 'Copia os textos .md do blog para o banco (o painel passa a editá-los).';

    public function handle(FonteDeArquivos $arquivos, SaneadorDeTexto $saneador): int
    {
        $lido = $arquivos->ler();
        foreach ($lido['erros'] as $arquivo => $erros) {
            $this->warn("{$arquivo}: fora do ar e fora da importação (".implode('; ', $erros).')');
        }

        $executar = (bool) $this->option('executar');
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
}
