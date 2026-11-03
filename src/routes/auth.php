<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\TwoFactor\AparelhosConfiaveisController;
use App\Http\Controllers\Auth\TwoFactor\ConfirmTwoFactorController;
use App\Http\Controllers\Auth\TwoFactor\DisableTwoFactorController;
use App\Http\Controllers\Auth\TwoFactor\EnableTwoFactorController;
use App\Http\Controllers\Auth\TwoFactor\ShowTwoFactorChallengeController;
use App\Http\Controllers\Auth\TwoFactor\StoreTwoFactorChallengeController;
use Illuminate\Support\Facades\Route;

/*
 * NÃO existe auto-cadastro aqui. O Breeze traz `/register`, e ele fica de
 * fora de propósito: as contas deste painel são o Ricardo e a equipe, criadas
 * por quem tem `usuarios.gerenciar` (ou `php artisan usuarios:criar`). Site
 * com cadastro aberto é um painel de manutenção público.
 *
 * Tudo mora sob `/painel` (03/11/2026): o site público não tem conta nenhuma.
 *
 * `tests/Feature/Auth/SemAutoCadastroTest.php` afirma a ausência, porque
 * **ausência de rota não quebra teste nenhum sozinha** — e a rota volta fácil,
 * por um `breeze:install` ou por um merge que traga o arquivo de um irmão. Foi
 * o que aconteceu no BHD.

 */
Route::middleware('guest')->prefix('painel')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])
        ->name('login');

    // Prefixo no throttle é obrigatório: sem ele o Laravel usa UM balde por IP
    // para todo `throttle:N,M` do domínio, e quem navegou chegava ao login
    // barrado. Teto da casa: 5 (29/09/2026).
    Route::post('login', [AuthenticatedSessionController::class, 'store'])->middleware('throttle:5,1,login');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->name('password.email')->middleware('throttle:5,1,esqueci-senha');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store')->middleware('throttle:5,1,redefinir-senha');

    // Segundo fator: ainda é `guest` (entre a senha e o código NÃO existe
    // sessão autenticada — só a marca `2fa.user_id`). O throttle segura a
    // origem; o teto por conta está no controller.
    Route::get('two-factor-challenge', ShowTwoFactorChallengeController::class)
        ->name('two-factor.challenge');

    Route::post('two-factor-challenge', StoreTwoFactorChallengeController::class)
        ->middleware('throttle:5,1,2fa');
});

Route::middleware('auth')->prefix('painel')->group(function () {
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])
        ->name('password.confirm');

    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::prefix('two-factor')->name('two-factor.')->group(function () {
        Route::post('enable', EnableTwoFactorController::class)->name('enable');
        Route::post('confirm', ConfirmTwoFactorController::class)
            ->middleware('throttle:6,1,2fa-confirmar')
            ->name('confirm');
        Route::delete('disable', DisableTwoFactorController::class)->name('disable');

        // Aparelhos confiáveis: remover um, ou todos. O `whereUuid` é o que
        // impede "todos" de casar com a rota de um só.
        Route::delete('aparelhos/{aparelho}', [AparelhosConfiaveisController::class, 'destroy'])
            ->whereUuid('aparelho')
            ->name('aparelhos.destroy');
        Route::delete('aparelhos', [AparelhosConfiaveisController::class, 'destroyAll'])
            ->name('aparelhos.destroy-all');
    });

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
        ->name('logout');
});
