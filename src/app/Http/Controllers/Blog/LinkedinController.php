<?php

declare(strict_types=1);

namespace App\Http\Controllers\Blog;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Symfony\Component\HttpFoundation\RedirectResponse as RedirecionamentoExterno;
use Throwable;
use UrbanoDev\Blog\Blog;

/**
 * "Comentar com o LinkedIn" (bloco 3b da 246, 04/11/2026).
 *
 * Não é login no site: o LinkedIn só confirma nome e e-mail, que ficam na
 * sessão para o formulário de comentário. Sem as chaves no `.env`, tudo aqui
 * dá 404 e o botão nem aparece. A foto que o LinkedIn devolve é ignorada:
 * mostrar a imagem do servidor deles avisaria o LinkedIn de cada visita.
 */
class LinkedinController extends Controller
{
    private const SESSAO_VOLTA = 'blog.linkedin_volta';

    public static function disponivel(): bool
    {
        return filled(config('services.linkedin-openid.client_id')) && filled(config('services.linkedin-openid.client_secret'));
    }

    public function entrar(Request $request, Blog $blog): RedirecionamentoExterno
    {
        abort_unless(self::disponivel(), 404);

        $volta = $request->query('volta');
        abort_unless(is_string($volta) && $blog->achar($volta) !== null, 404);
        $request->session()->put(self::SESSAO_VOLTA, $volta);

        return Socialite::driver('linkedin-openid')->redirect();
    }

    public function retorno(Request $request): RedirectResponse
    {
        abort_unless(self::disponivel(), 404);
        $volta = url('/blog/'.$request->session()->pull(self::SESSAO_VOLTA, '')).'#comentar';

        try {
            $usuario = Socialite::driver('linkedin-openid')->user();
        } catch (Throwable $e) {
            // Inclui quem clicou em "Cancelar" na tela do LinkedIn.
            Log::info('Entrada pelo LinkedIn não concluída', ['erro' => $e->getMessage()]);

            return redirect()->to($volta)->withErrors(['linkedin' => 'Não deu para entrar com o LinkedIn. Você pode comentar com nome e e-mail.'], 'comentario');
        }

        $email = $usuario->getEmail();
        $nome = trim((string) $usuario->getName());
        if (! filled($email) || $nome === '') {
            return redirect()->to($volta)->withErrors(['linkedin' => 'O LinkedIn não informou nome e e-mail. Você pode comentar com nome e e-mail.'], 'comentario');
        }

        $request->session()->put(LeitoresController::SESSAO_LINKEDIN, [
            'sub' => mb_substr((string) $usuario->getId(), 0, 120),
            'nome' => mb_substr($nome, 0, 120),
            'email' => mb_substr((string) $email, 0, 255),
        ]);

        return redirect()->to($volta);
    }

    public function sair(Request $request): RedirectResponse
    {
        $request->session()->forget(LeitoresController::SESSAO_LINKEDIN);
        $slug = $request->input('volta');

        return redirect()->to(is_string($slug) && preg_match('/^[a-z0-9-]+$/', $slug) ? url('/blog/'.$slug).'#comentar' : url('/blog'));
    }
}
