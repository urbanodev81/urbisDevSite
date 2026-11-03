<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Perfis;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Perfis e permissões do painel do site (03/11/2026, pendência 246).
 *
 * Veio do molde do Duo (que veio do BootHelpDesk). O que foi mantido, e não é
 * simplificável:
 *
 * 1. **Perfis vivem em banco, não em enum.** A fonte da verdade é a tabela
 *    `roles`; `App\Support\Perfis` só dá nome legível aos dois que nascem aqui.
 * 2. **Permissão por tela**, e o menu só mostra o que a pessoa pode abrir —
 *    mas quem decide é a rota (`can:` no `routes/web.php`), não o menu.
 *
 * As permissões de leads, blog e configurações já nascem aqui, antes das
 * telas (blocos 2 a 4 da 246): é o que dá sentido ao perfil `redacao` desde o
 * primeiro dia, e evita a armadilha abaixo quando as telas chegarem.
 *
 * ⚠️ **ARMADILHA JÁ CONHECIDA:** permissão nasce no seeder, e em ambiente que
 * JÁ EXISTE o `migrate` não a cria sozinho. Por isso a migration
 * `2026_11_03_100000_semeia_perfis_e_permissoes` roda esta classe; permissão
 * nova = migration nova que a chama de novo (ela é idempotente).
 */
class PerfisESeguranca extends Seeder
{
    /** @var array<string, array<int, string>> */
    private const PERMISSOES = [
        'Contatos' => [
            'leads.ver',
            'leads.gerenciar',
        ],
        'Blog' => [
            'blog.gerenciar',
        ],
        'Administração' => [
            'configuracoes.gerenciar',
            'usuarios.ver',
            'usuarios.gerenciar',
            'auditoria.ver',
        ],
    ];

    /** @var array<string, '*'|array<int, string>> */
    private const PERFIS = [
        Perfis::ADMINISTRADOR => '*',

        // A equipe que escreve: textos do blog e a caixa de contatos (quem
        // escreve o texto é quem vê se ele trouxe gente). Sem contas de acesso,
        // sem configuração do site, sem a trilha de auditoria.
        Perfis::REDACAO => [
            'leads.ver', 'leads.gerenciar',
            'blog.gerenciar',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $todas = [];

        foreach (self::PERMISSOES as $permissoes) {
            foreach ($permissoes as $nome) {
                Permission::findOrCreate($nome, 'web');
                $todas[] = $nome;
            }
        }

        foreach (self::PERFIS as $perfil => $permissoes) {
            // `sync`, não `give`: o seeder é a fonte da verdade do que cada
            // perfil nasce podendo. Rodar de novo corrige deriva.
            Role::findOrCreate($perfil, 'web')
                ->syncPermissions($permissoes === '*' ? $todas : $permissoes);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** @return array<string, array<int, string>> */
    public static function grupos(): array
    {
        return self::PERMISSOES;
    }
}
