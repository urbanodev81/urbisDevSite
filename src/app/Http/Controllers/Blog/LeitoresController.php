<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Blog\Leitores;
use App\Http\Controllers\Controller;
use App\Models\BlogComentario;
use App\Models\BlogVoto;
use App\Support\Alertas\EmailFormatado;
use App\Support\Captcha\Altcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Throwable;
use UrbanoDev\Blog\Blog;

/**
 * O que o leitor faz no fim de um texto do blog (bloco 3b da 246, 04/11/2026):
 * conta a leitura, responde "Este texto ajudou?" e comenta.
 *
 * Tudo é formulário comum (funciona sem JS) e volta para a âncora do bloco.
 * Só a leitura é por `sendBeacon`: é justamente o JS que separa gente de robô.
 * Banco fora: a página segue de pé, e o leitor vê um aviso em vez de 500.
 */
class LeitoresController extends Controller
{
    /** Sessão: o comentarista que entrou pelo LinkedIn ({sub, nome, email}). */
    public const SESSAO_LINKEDIN = 'blog.comentarista';

    public function lido(Request $request, string $slug, Leitores $leitores): Response
    {
        $lidos = (array) $request->session()->get('blog.lidos', []);

        if (! Leitores::ehRobo($request->userAgent()) && ! in_array($slug, $lidos, true) && $leitores->registrarLeitura($slug)) {
            // Teto de 200 slugs na sessão: ninguém lê 200 textos numa visita.
            $request->session()->put('blog.lidos', array_slice([...$lidos, $slug], -200));
        }

        return response()->noContent();
    }

    public function ajudou(Request $request, string $slug, Blog $blog): RedirectResponse
    {
        abort_if($blog->achar($slug) === null, 404);
        $volta = url('/blog/'.$slug).'#ajudou';
        $chave = 'blog.votos.'.$slug;

        $faltou = $this->semTagNemLink($request->input('faltou'), 1000);

        try {
            $votoId = $request->session()->get($chave);

            // Segundo passo do "Não": o leitor conta o que faltou no voto que já deu.
            if ($votoId !== null) {
                if ($faltou !== null) {
                    BlogVoto::query()->whereKey($votoId)->where('util', false)->whereNull('faltou')->update(['faltou' => $faltou]);
                    $request->session()->flash('blog.voto', 'faltou');
                }

                return redirect()->to($volta);
            }

            $util = match ($request->input('util')) {
                'sim' => true,
                'nao' => false,
                default => null,
            };
            if ($util === null) {
                return redirect()->to($volta);
            }

            $voto = BlogVoto::create(['slug' => $slug, 'util' => $util, 'faltou' => $util ? null : $faltou]);
            $request->session()->put($chave, $voto->id);
            $request->session()->flash('blog.voto', $util ? 'sim' : ($faltou !== null ? 'faltou' : 'nao'));
        } catch (Throwable $e) {
            Log::warning('Voto do blog não gravado', ['slug' => $slug, 'erro' => $e->getMessage()]);
            $request->session()->flash('blog.voto', 'erro');
        }

        return redirect()->to($volta);
    }

    public function comentar(Request $request, string $slug, Blog $blog, Altcha $captcha): RedirectResponse
    {
        $post = $blog->achar($slug);
        abort_if($post === null, 404);
        $volta = url('/blog/'.$slug).'#comentar';

        $linkedin = $request->session()->get(self::SESSAO_LINKEDIN);
        $request->merge(['corpo' => is_string($request->input('corpo')) ? trim(strip_tags($request->input('corpo'))) : $request->input('corpo')]);

        $regras = [
            'corpo' => ['required', 'string', 'min:10', 'max:3000', function (string $a, mixed $v, \Closure $falha): void {
                // Comentário sem link (decisão dele): é onde o spam mora.
                if (is_string($v) && preg_match('~(?:https?://|www\.)\S+|\b[\w-]+\.(?:com|net|org|io|br|xyz|ru|top)\b/?~i', $v) === 1) {
                    $falha('Tire o link do comentário e envie de novo.');
                }
            }],
            'consent' => 'required|accepted',
        ];
        if ($linkedin === null) {
            $regras += [
                'nome' => 'required|string|min:2|max:120',
                'email' => 'required|email|max:255',
                'captcha_token' => 'required|string',
            ];
        }

        $validador = Validator::make($request->all(), $regras, [
            'captcha_token.required' => 'Aguarde a verificação de segurança terminar e envie de novo.',
            'consent.required' => 'Marque a autorização para enviar.',
            'consent.accepted' => 'Marque a autorização para enviar.',
        ], ['corpo' => 'comentário', 'nome' => 'nome', 'email' => 'e-mail']);

        if ($validador->fails()) {
            return redirect()->to($volta)->withErrors($validador, 'comentario')->withInput();
        }
        $dados = $validador->validated();

        // O LinkedIn já disse quem é: o captcha só vale para o formulário.
        if ($linkedin === null && ! $captcha->verificar($dados['captcha_token'])) {
            return redirect()->to($volta)->withErrors(['captcha_token' => 'Falha na verificação de segurança. Recarregue a página e tente novamente.'], 'comentario')->withInput();
        }

        $nome = $linkedin['nome'] ?? trim(strip_tags($dados['nome']));
        $email = mb_strtolower($linkedin['email'] ?? $dados['email']);

        // Mesmo recado duas vezes (clique repetido) vira um só, como no contato.
        if (! Cache::add('blog:comentario:'.hash('sha256', $slug.'|'.$email.'|'.$dados['corpo']), true, 300)) {
            return redirect()->to($volta)->with('blog.comentario', 'recebido');
        }

        try {
            $comentario = BlogComentario::create([
                'slug' => $slug,
                'nome' => $nome,
                'email' => $email,
                'origem' => $linkedin ? BlogComentario::ORIGEM_LINKEDIN : BlogComentario::ORIGEM_FORMULARIO,
                'linkedin_sub' => $linkedin['sub'] ?? null,
                'corpo' => $dados['corpo'],
                'consentimento_em' => now(),
            ]);
        } catch (Throwable $e) {
            Log::error('Comentário do blog não gravado', ['slug' => $slug, 'erro' => $e->getMessage()]);

            return redirect()->to($volta)->withErrors(['envio' => 'Não conseguimos guardar o comentário agora. Tente de novo em alguns minutos.'], 'comentario')->withInput();
        }

        try {
            EmailFormatado::para((string) config('mail.from.address'))
                ->assunto('Comentário no blog — '.$post->titulo)
                ->titulo('Comentário esperando moderação')
                ->campos([
                    'Texto' => $post->titulo,
                    'Nome' => $nome.($linkedin ? ' (verificado pelo LinkedIn)' : ''),
                    'E-mail' => $email,
                ])
                ->paragrafo($dados['corpo'])
                ->paragrafo('Aprove ou recuse em '.route('painel.blog.comentarios.index'))
                ->responderPara($email, $nome)
                ->assinatura('Comentários do blog — urbisdev.tech')
                ->enviar();
            $comentario->forceFill(['email_enviado_em' => now()])->saveQuietly();
        } catch (Throwable $e) {
            // O comentário já está na fila do painel; o aviso é conforto.
            Log::warning('Aviso de comentário do blog não saiu', ['comentario' => $comentario->uuid, 'erro' => $e->getMessage()]);
        }

        return redirect()->to($volta)->with('blog.comentario', 'recebido');
    }

    private function semTagNemLink(mixed $texto, int $max): ?string
    {
        if (! is_string($texto)) {
            return null;
        }
        $limpo = trim(preg_replace('~(?:https?://|www\.)\S+~i', '', strip_tags($texto)) ?? '');

        return $limpo === '' ? null : mb_substr($limpo, 0, $max);
    }
}
