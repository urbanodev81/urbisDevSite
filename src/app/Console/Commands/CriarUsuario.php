<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\User;
use App\Support\Perfis;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Cria a PRIMEIRA conta, em produção.
 *
 * Existe porque o `DatabaseSeeder` não cria administrador em produção — seeder
 * com senha conhecida é uma porta com a chave embaixo do tapete, e ela
 * sobrevive por meses porque ninguém lembra que existe.
 *
 * A senha é pedida em modo oculto e passa pela mesma regra da tela
 * (`Password::defaults()`). Aceitar `--password` na linha de comando deixaria
 * a senha no histórico do shell.
 */
class CriarUsuario extends Command
{
    protected $signature = 'usuarios:criar {--nome=} {--email=} {--perfil=administrador}';

    protected $description = 'Cria uma conta de acesso ao painel.';

    public function handle(): int
    {
        $nome = $this->option('nome') ?: $this->ask('Nome');
        $email = $this->option('email') ?: $this->ask('E-mail');
        $perfil = (string) $this->option('perfil');
        $senha = $this->secret('Senha');

        if (Role::query()->where('name', $perfil)->doesntExist()) {
            $this->error("Perfil '{$perfil}' não existe. Rode `php artisan db:seed --class=PerfisESeguranca` antes.");

            return self::FAILURE;
        }

        $validador = Validator::make(
            ['name' => $nome, 'email' => $email, 'password' => $senha],
            [
                'name' => ['required', 'string', 'max:255'],
                'email' => ['required', 'email', 'max:255', 'unique:users,email'],
                'password' => ['required', Password::defaults()],
            ],
        );

        if ($validador->fails()) {
            foreach ($validador->errors()->all() as $erro) {
                $this->error($erro);
            }

            return self::FAILURE;
        }

        $usuario = User::create([
            'name' => $nome,
            'email' => $email,
            'password' => Hash::make($senha),
            'ativo' => true,
            'email_verified_at' => now(),
        ]);

        $usuario->syncRoles([$perfil]);

        $this->info("Conta criada: {$email} · ".(Perfis::rotulo($perfil) ?? $perfil));

        return self::SUCCESS;
    }
}
