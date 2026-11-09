<?php

use App\Http\Middleware\ExigirSegundoFator;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use App\Support\Alertas\AlertaDeErro;
use App\Support\Portfolio;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
    )
    // Um por requisição: o casco (menu) e a página leem o mesmo portfólio.
    ->withSingletons([Portfolio::class])
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);

        // Painel (03/11/2026, pendência 246). O Inertia só age em resposta
        // Inertia; o `ExigirSegundoFator` só em rota com `auth` — as páginas
        // públicas passam pelos dois sem tocar no banco.
        $middleware->web(append: [
            HandleInertiaRequests::class,
            ExigirSegundoFator::class,
            // Visitas do site (09/11/2026, pendência 263): conta só as rotas de
            // config/visitas.php, de quem não está logado, sem cookie nem IP
            // guardado. Grava depois de a página sair; banco fora não derruba nada.
            \UrbanoDev\Visitas\Http\ContarVisita::class,
        ]);

        $middleware->redirectGuestsTo('/painel/login');
        $middleware->redirectUsersTo('/painel');

        $middleware->alias([
            'permission' => PermissionMiddleware::class,
            'role' => RoleMiddleware::class,
        ]);

        // O IP de quem visita (18/10/2026, pendência 136). Até aqui o Laravel
        // via todo mundo como o gateway do Docker (172.16.4.1), e cada
        // `throttle` do site era UM balde para a internet inteira: um robô no
        // contato travava o formulário de todos. Quem fala com o contêiner é
        // só o nginx do host (portas em 127.0.0.1 no docker-compose.yml), e ele
        // SOBRESCREVE estes dois cabeçalhos com o que viu. Só estes dois: o
        // host repassa um X-Forwarded-Host que viesse do visitante, e confiar
        // nele deixaria qualquer um escolher o domínio dos links gerados.
        $middleware->trustProxies(
            at: '*',
            headers: Request::HEADER_X_FORWARDED_FOR | Request::HEADER_X_FORWARDED_PROTO,
        );
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // E-mail ao operador em todo erro reportável, com freio de 5 min por
        // erro distinto — até 29/09/2026 nenhum 500 do site avisava ninguém
        // (pendência 134).
        $exceptions->reportable(fn (Throwable $e) => AlertaDeErro::avisar($e));

        // Erro DENTRO do painel sai na página de erro do painel (Inertia);
        // no site público segue a `errors/404.blade.php` de sempre.
        $exceptions->respond(function (Response $response, Throwable $e, Request $request) {
            $status = $response->getStatusCode();

            if (! $request->is('painel', 'painel/*')
                || ($request->expectsJson() && ! $request->header('X-Inertia'))
                || ! in_array($status, [403, 404, 419, 429, 500, 503], true)
                || ($status === 500 && config('app.debug'))) {
                return $response;
            }

            return Inertia::render('Error', ['status' => $status])
                ->toResponse($request)
                ->setStatusCode($status);
        });
    })
    ->create();
