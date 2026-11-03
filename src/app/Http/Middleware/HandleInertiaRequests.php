<?php

namespace App\Http\Middleware;

use App\Support\Perfis;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $usuario = $request->user();

        return [
            ...parent::share($request),
            // O nome no menu e na porta do painel.
            'marca' => [
                'nome' => config('marca.nome'),
                'inicial' => config('marca.inicial'),
            ],
            'auth' => [
                'user' => $usuario,

                /*
                 * Nome legível do perfil, para o menu da conta dizer com que
                 * papel a pessoa está olhando o painel. Só o primeiro; a tela
                 * de usuários mostra todos.
                 */
                'perfil' => Perfis::rotulo($usuario?->getRoleNames()->first()),

                /*
                 * O menu só mostra o que a pessoa pode abrir.
                 *
                 * Isto é conforto de navegação, NÃO controle de acesso — quem
                 * decide continua sendo a policy no controller. Esconder o
                 * link sem proteger a rota seria o erro clássico; proteger a
                 * rota e ainda assim oferecer o link é só deixar o
                 * terceirizado bater num 403 todo dia.
                 *
                 * `can()` em vez de `hasPermissionTo()` porque aquele devolve
                 * false quando a permissão ainda não foi semeada, em vez de
                 * estourar a tela inteira.
                 */
                'permissoes' => [
                    'leads' => (bool) $usuario?->can('leads.ver'),
                    'blog' => (bool) $usuario?->can('blog.gerenciar'),
                    'configuracoes' => (bool) $usuario?->can('configuracoes.gerenciar'),
                    'usuarios' => (bool) $usuario?->can('usuarios.ver'),
                    'auditoria' => (bool) $usuario?->can('auditoria.ver'),
                ],
            ],

            /*
             * Recado de uma ação que já aconteceu ("conta criada"). Closure
             * para ser avaliado a cada resposta: `session()->get()` fora dela
             * seria resolvido uma vez e o mesmo recado voltaria na navegação
             * seguinte.
             */
            'flash' => [
                'sucesso' => fn () => $request->session()->get('sucesso'),

                // O par negativo existe desde o relato de erro (10/08/2026): ali
                // um "obrigado!" quando o envio falhou faria a pessoa parar de
                // relatar achando que já avisou. Ação que pode não dar certo
                // precisa de canal para dizer isso.
                'erro' => fn () => $request->session()->get('erro'),
            ],
        ];
    }
}
