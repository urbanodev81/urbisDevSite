<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Blog\GuardaImagens;
use App\Blog\SaneadorDeTexto;
use App\Http\Controllers\Controller;
use App\Models\Texto;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use UrbanoDev\Blog\Blog;
use UrbanoDev\Blog\FonteDeArquivos;
use UrbanoDev\Blog\Post;

/**
 * Blog no painel (bloco 3a da 246, 04/11/2026): escrever, agendar, arquivar.
 *
 * Tudo com `blog.gerenciar` (administrador e redação). Ninguém apaga daqui:
 * arquivar tira do ar e da lista, e restaurar devolve.
 *
 * O corpo passa pelo `SaneadorDeTexto` antes de gravar. O endereço (slug)
 * nasce do título e trava depois que o texto foi ao ar: link compartilhado não
 * pode passar a dar 404.
 */
class TextoController extends Controller
{
    private const IMAGEM_OU_ENDERECO = '~^(https://|/(?!/))~i';

    public function index(Request $request, Blog $blog, FonteDeArquivos $arquivos): Response
    {
        $aba = in_array($request->string('aba')->toString(), ['rascunho', 'agendado', 'publicado', 'arquivado', 'arquivo'], true)
            ? $request->string('aba')->toString() : 'publicado';

        $textos = Texto::query()->withTrashed()->orderByDesc('data')->orderByDesc('id')->get();
        $doBanco = $textos->pluck('slug')->all();

        // Os .md que o banco não cobre: aparecem para ninguém achar que sumiram.
        $soEmArquivo = array_values(array_filter($arquivos->ler()['posts'], fn (Post $p) => ! in_array($p->slug, $doBanco, true)));

        $contagem = ['rascunho' => 0, 'agendado' => 0, 'publicado' => 0, 'arquivado' => 0, 'arquivo' => count($soEmArquivo)];
        foreach ($textos as $t) {
            $contagem[$t->trashed() ? 'arquivado' : $t->situacao()]++;
        }

        $linhas = $aba === 'arquivo'
            ? array_map(fn (Post $p) => [
                'uuid' => null,
                'slug' => $p->slug,
                'titulo' => $p->titulo,
                'data' => $p->data->format('d/m/Y'),
                'situacao' => $p->rascunho ? 'rascunho' : ($p->publicado() ? 'publicado' : 'agendado'),
                'secao' => $p->secao,
                'url' => $p->publicado() ? url('/blog/'.$p->slug) : null,
                'previa' => $p->agendado() ? route('painel.blog.previa', $p->slug) : null,
            ], $soEmArquivo)
            : $textos
                ->filter(fn (Texto $t) => $aba === 'arquivado' ? $t->trashed() : (! $t->trashed() && $t->situacao() === $aba))
                ->map(fn (Texto $t) => [
                    'uuid' => $t->uuid,
                    'slug' => $t->slug,
                    'titulo' => $t->titulo,
                    'data' => $t->data->format('d/m/Y'),
                    'situacao' => $t->situacao(),
                    'secao' => $t->secao,
                    'url' => ! $t->trashed() && $t->situacao() === 'publicado' ? url('/blog/'.$t->slug) : null,
                    'previa' => $t->trashed() ? null : route('painel.blog.textos.previa', $t),
                ])->values()->all();

        return Inertia::render('Painel/Blog/Index', [
            'aba' => $aba,
            'contagem' => $contagem,
            'textos' => $linhas,
        ]);
    }

    public function create(Blog $blog): Response
    {
        return Inertia::render('Painel/Blog/Editar', [
            'texto' => null,
            'secoes' => $this->secoesConhecidas($blog),
        ]);
    }

    public function store(Request $request, SaneadorDeTexto $saneador, FonteDeArquivos $arquivos): RedirectResponse
    {
        $dados = $this->validar($request, null, $saneador);

        $slug = ($dados['slug'] ?? null) ?: $this->slugLivre($dados['titulo']);
        if (collect($arquivos->ler()['posts'])->contains(fn (Post $p) => $p->slug === $slug)) {
            throw ValidationException::withMessages(['slug' => 'Já existe um texto em arquivo (.md) com este endereço. Escolha outro, ou importe os .md para o painel.']);
        }

        $texto = Texto::create([...$dados, 'slug' => $slug, 'origem' => 'painel']);

        return redirect()->route('painel.blog.textos.edit', $texto)->with('sucesso', $this->mensagem($texto, 'Texto criado'));
    }

    public function edit(Texto $texto, Blog $blog): Response
    {
        return Inertia::render('Painel/Blog/Editar', [
            'texto' => [
                'uuid' => $texto->uuid,
                'slug' => $texto->slug,
                'titulo' => $texto->titulo,
                'descricao' => $texto->descricao,
                'corpo_html' => $texto->corpo_html,
                'data_publicacao' => $texto->data->format('Y-m-d'),
                'atualizado' => $texto->atualizado?->format('Y-m-d'),
                'rascunho' => $texto->rascunho,
                'autor' => $texto->autor,
                'secao' => $texto->secao,
                'tags' => $texto->tags ?? [],
                'imagem' => $texto->imagem,
                'imagem_alt' => $texto->imagem_alt,
                'imagem_credito' => $texto->imagem_credito,
                'acao_texto' => $texto->acao_texto,
                'acao_url' => $texto->acao_url,
                'parceiro' => $texto->parceiro,
                'parceiro_logo' => $texto->parceiro_logo,
                'parceiro_url' => $texto->parceiro_url,
                'situacao' => $texto->situacao(),
                'slugTravado' => $texto->jaFoiPublicado(),
                'url' => $texto->situacao() === 'publicado' ? url('/blog/'.$texto->slug) : null,
                'previa' => route('painel.blog.textos.previa', $texto),
                'origem' => $texto->origem,
            ],
            'secoes' => $this->secoesConhecidas($blog),
        ]);
    }

    public function update(Request $request, Texto $texto, SaneadorDeTexto $saneador): RedirectResponse
    {
        $dados = $this->validar($request, $texto, $saneador);
        $dados['slug'] = ($dados['slug'] ?? null) ?: $texto->slug;

        if ($texto->jaFoiPublicado() && $dados['slug'] !== $texto->slug) {
            throw ValidationException::withMessages(['slug' => 'O texto já foi ao ar com este endereço: ele não muda mais (quem guardou o link ficaria sem nada).']);
        }

        $texto->update($dados);

        return back()->with('sucesso', $this->mensagem($texto, 'Texto salvo'));
    }

    public function destroy(Texto $texto): RedirectResponse
    {
        $texto->delete();

        return redirect()->route('painel.blog.textos.index', ['aba' => 'arquivado'])
            ->with('sucesso', 'Texto arquivado: saiu do site. Dá para restaurar quando quiser.');
    }

    public function restaurar(string $texto): RedirectResponse
    {
        $t = Texto::onlyTrashed()->where('uuid', Str::isUuid($texto) ? $texto : '')->firstOrFail();
        $t->restore();

        return redirect()->route('painel.blog.textos.edit', $t)->with('sucesso', $this->mensagem($t, 'Texto restaurado'));
    }

    /** Imagem do editor ou da capa. Devolve o endereço para o editor pôr no texto. */
    public function imagem(Request $request, GuardaImagens $guarda): JsonResponse
    {
        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192', 'dimensions:min_width=200,min_height=100,max_width=8000,max_height=8000'],
            'capa' => ['sometimes', 'boolean'],
        ], [
            'arquivo.mimes' => 'Envie uma foto em JPG, PNG ou WebP.',
            'arquivo.max' => 'A imagem passa de 8 MB.',
            'arquivo.dimensions' => 'A imagem precisa ter pelo menos 200×100 e no máximo 8000 px de lado.',
        ]);

        try {
            $imagem = $guarda->guardar($request->file('arquivo'), $request->boolean('capa'), $request->user()?->getKey());
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['arquivo' => $e->getMessage()]);
        }

        return response()->json([
            'url' => $imagem->url(),
            'largura' => $imagem->largura,
            'altura' => $imagem->altura,
        ], 201);
    }

    /** @return array<string, mixed> */
    private function validar(Request $request, ?Texto $texto, SaneadorDeTexto $saneador): array
    {
        $request->merge(['tags' => array_values(array_filter(array_map(
            fn ($t) => is_string($t) ? trim($t) : $t,
            (array) $request->input('tags', [])
        ), fn ($t) => $t !== '' && $t !== null))]);

        $dados = $request->validate([
            'titulo' => ['required', 'string', 'max:255'],
            'descricao' => ['required', 'string', 'max:300'],
            'corpo_html' => ['required', 'string', 'max:500000'],
            // `data_publicacao` e não `data`: o `useForm` do Inertia tem um método
            // `data()`, e o campo com esse nome quebrava a tela de edição.
            'data_publicacao' => ['required', 'date_format:Y-m-d'],
            'atualizado' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:data_publicacao'],
            'rascunho' => ['required', 'boolean'],
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^'.Blog::SLUG.'$/D', 'not_regex:/^\d{4}$/',
                Rule::unique('textos', 'slug')->ignore($texto?->id)],
            'autor' => ['nullable', 'string', 'max:120'],
            'secao' => ['nullable', 'string', 'max:60', 'regex:/[\pL\pN]/u'],
            'tags' => ['array', 'max:8'],
            'tags.*' => ['string', 'max:40', 'regex:/[\pL\pN]/u'],
            'imagem' => ['nullable', 'string', 'max:255', 'regex:'.self::IMAGEM_OU_ENDERECO],
            'imagem_alt' => ['nullable', 'required_with:imagem', 'string', 'max:300'],
            'imagem_credito' => ['nullable', 'string', 'max:200'],
            'acao_texto' => ['nullable', 'required_with:acao_url', 'string', 'max:80'],
            'acao_url' => ['nullable', 'string', 'max:255', 'regex:'.self::IMAGEM_OU_ENDERECO],
            'parceiro' => ['nullable', 'required_with:parceiro_logo,parceiro_url', 'string', 'max:120'],
            'parceiro_logo' => ['nullable', 'string', 'max:255', 'regex:'.self::IMAGEM_OU_ENDERECO],
            'parceiro_url' => ['nullable', 'string', 'max:255', 'regex:'.self::IMAGEM_OU_ENDERECO],
        ], [
            'slug.regex' => 'Use só letras minúsculas sem acento, números e hífen.',
            'slug.not_regex' => 'O endereço não pode ser só um ano: /blog/2026 é o arquivo daquele ano.',
            'slug.unique' => 'Já existe um texto com este endereço (talvez arquivado).',
            'imagem_alt.required_with' => 'Descreva a capa para quem não enxerga a imagem.',
            '*.regex' => 'Use um endereço que comece com https:// ou /.',
        ]);

        $dados['data'] = $dados['data_publicacao'];
        unset($dados['data_publicacao']);

        $dados['corpo_html'] = $saneador->limpar($dados['corpo_html']);
        if (trim(strip_tags($dados['corpo_html'], '<img><figure>')) === '') {
            throw ValidationException::withMessages(['corpo_html' => 'O texto está vazio.']);
        }
        $dados['tags'] = $dados['tags'] === [] ? null : $dados['tags'];

        return $dados;
    }

    private function slugLivre(string $titulo): string
    {
        $base = Str::limit(Str::slug($titulo), 100, '') ?: 'texto';
        $base = preg_match('/^\d{4}$/', $base) === 1 ? 'texto-'.$base : $base;
        $slug = $base;
        for ($n = 2; Texto::withTrashed()->where('slug', $slug)->exists(); $n++) {
            $slug = $base.'-'.$n;
        }

        return $slug;
    }

    /** @return list<string> as seções que já existem, para o campo sugerir e ninguém criar "Experiencia" sem acento. */
    private function secoesConhecidas(Blog $blog): array
    {
        return array_values(array_unique([
            ...array_values($blog->secoes()),
            ...Texto::query()->whereNotNull('secao')->distinct()->pluck('secao')->all(),
        ]));
    }

    private function mensagem(Texto $texto, string $acao): string
    {
        return $acao.match ($texto->situacao()) {
            'rascunho' => ' como rascunho: só o painel vê.',
            'agendado' => ': entra no ar sozinho em '.$texto->data->format('d/m/Y').'.',
            default => ' e no ar.',
        };
    }
}
