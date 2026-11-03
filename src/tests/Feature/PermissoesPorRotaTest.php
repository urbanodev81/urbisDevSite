<?php

declare(strict_types=1);

use App\Models\User;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;

/**
 * A prova é POR ROTA, com sessão real, atravessando middleware, binding e
 * permissão na ordem verdadeira.
 *
 * Teste que chama o método do controller direto já ficou verde com falha de pé
 * no BHD — é a razão de este arquivo usar `$this->get(rota)`.
 */
beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
});

function usuarioCom(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Teste '.$perfil,
        'email' => $perfil.'@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);

    $usuario->syncRoles([$perfil]);

    return $usuario;
}

it('exige autenticação em todo o painel', function (string $rota) {
    $this->get($rota)->assertRedirect(route('login'));
})->with([
    '/painel',
    '/painel/usuarios',
    '/painel/auditoria',
    '/painel/perfil',
]);

it('a redação abre o início e a própria conta', function (string $rota) {
    $this->actingAs(usuarioCom(Perfis::REDACAO))->get($rota)->assertOk();
})->with([
    '/painel',
    '/painel/perfil',
]);

it('a redação não abre contas de acesso nem a trilha', function (string $rota) {
    $this->actingAs(usuarioCom(Perfis::REDACAO))->get($rota)->assertForbidden();
})->with([
    '/painel/usuarios',
    '/painel/usuarios/criar/novo',
    '/painel/auditoria',
]);

it('o administrador abre tudo', function (string $rota) {
    $this->actingAs(usuarioCom(Perfis::ADMINISTRADOR))->get($rota)->assertOk();
})->with([
    '/painel',
    '/painel/usuarios',
    '/painel/usuarios/criar/novo',
    '/painel/auditoria',
    '/painel/perfil',
]);

it('a redação não cria conta nem pelo POST direto', function () {
    $this->actingAs(usuarioCom(Perfis::REDACAO))
        ->post('/painel/usuarios', ['name' => 'X', 'email' => 'x@x.local'])
        ->assertForbidden();
});
