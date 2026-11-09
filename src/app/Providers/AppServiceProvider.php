<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\User;
use App\Support\Security\AlertaDeSeguranca;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

/**
 * Eventos do login do painel (03/11/2026, pendência 246), no formato do molde.
 */
class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // A configuração do site nas views: `{{ $site('contato.email') }}`.
        // É um objeto que só consulta quando chamado, então o painel e o console
        // não pagam a leitura (bloco 4a, 06/11/2026).
        \Illuminate\Support\Facades\View::share('site', new \App\Support\ConfiguracaoDoSite);

        // Imagens pelo pacote urbanodev/midia (255, 07/11/2026): a galeria e o
        // envio servem o Blog e o Site, então vale quem edita um ou outro.
        \Illuminate\Support\Facades\Gate::define('midia.usar', fn (User $u) => $u->can('blog.gerenciar') || $u->can('configuracoes.gerenciar'));

        // Visitas do site (263, 09/11/2026): número agregado, sem dado de
        // ninguém. Vê quem cuida dos contatos ou do site — os dois perfis.
        \Illuminate\Support\Facades\Gate::define('visitas.ver', fn (User $u) => $u->can('leads.ver') || $u->can('configuracoes.gerenciar'));

        // Conta travada por senha errada (LoginRequest): um aviso ao operador
        // por e-mail+IP a cada 15 min. Pode ser o dono esquecido — ou força bruta.
        Event::listen(Lockout::class, function (Lockout $e) {
            $email = Str::lower((string) $e->request->input('email'));

            AlertaDeSeguranca::avisar(
                'login.travado',
                'Conta travada por excesso de senhas erradas',
                'Cinco senhas erradas para a mesma conta do painel. Pode ser o dono esquecido — ou força bruta.',
                [
                    'target_email' => $email ?: null,
                    'actor_ip' => $e->request->ip(),
                    'user_agent' => $e->request->userAgent(),
                    'changes' => ['host' => $e->request->getHost()],
                ],
                freio: $email.'|'.$e->request->ip(),
            );
        });

        // "Último acesso" na tela de contas. O relato do login ao Sócrates
        // (outbox) chega com o bloco 2.
        Event::listen(Login::class, function (Login $evento): void {
            if ($evento->user instanceof User) {
                $evento->user->registrarAcesso();
            }
        });
    }
}
