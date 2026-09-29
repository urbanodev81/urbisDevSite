<?php

use App\Http\Middleware\SecurityHeaders;
use App\Support\Alertas\AlertaDeErro;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(SecurityHeaders::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // E-mail ao operador em todo erro reportável, com freio de 5 min por
        // erro distinto — até 29/09/2026 nenhum 500 do site avisava ninguém
        // (pendência 134).
        $exceptions->reportable(fn (Throwable $e) => AlertaDeErro::avisar($e));
    })
    ->create();
