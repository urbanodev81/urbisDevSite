<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Auth\AparelhosConfiaveis;
use App\Support\Auditoria\Auditable;
use App\Support\Perfis;
use App\Support\TemUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Uma conta de acesso ao painel.
 *
 * **Não existe auto-cadastro.** A rota `/register` do Breeze não está em
 * `routes/auth.php`, e isso é decisão, não esquecimento: as contas são o
 * Ricardo e quem ele criar para a equipe (tela Usuários ou `usuarios:criar`).
 * Site com cadastro aberto é um painel de manutenção público.
 *
 * `tests/Feature/Auth/SemAutoCadastroTest.php` existe porque **ausência de
 * rota não quebra teste nenhum sozinha** — precisa de quem a afirme. É a mesma
 * lição do BHD, que perdeu a rota uma vez e a recuperou num merge.
 */
class User extends Authenticatable
{
    use Auditable;
    use HasFactory;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;
    use TemUuid;

    protected $guarded = ['id'];

    protected static function booted(): void
    {
        /*
         * Aparelho confiável não sobrevive à troca de senha nem ao
         * desligamento do segundo fator (10/10/2026). Aqui, e não em cada
         * controller: são quatro portas hoje (trocar a senha, redefinir pelo
         * e-mail, desligar o próprio, desligar o de outra pessoa), e a quinta
         * que alguém escrever esqueceria. Quem troca a senha por desconfiança
         * fecha a porta inteira; segredo novo não herda confiança do antigo.
         */
        static::updated(function (self $u): void {
            $desligou = $u->wasChanged('dois_fatores_confirmado_em') && $u->dois_fatores_confirmado_em === null;

            if ($u->wasChanged('password') || $desligou) {
                app(AparelhosConfiaveis::class)->removerTodos($u);
            }
        });
    }

    /**
     * Espelha o default do banco.
     *
     * Coluna com `default(true)` só vale na leitura SEGUINTE: logo após o
     * `create()` o atributo é `null` em memória, e `! $u->ativo` vira true —
     * quem acabou de ser criado não conseguiria entrar. Armadilha já paga nos
     * irmãos; repita em todo model com flag.
     */
    protected $attributes = ['ativo' => true];

    protected $hidden = [
        'password',
        'remember_token',
        // Cast `encrypted` decripta ao acessar: um `$user` inteiro numa prop
        // de Inertia entregaria a chave do aplicativo no HTML da página.
        'dois_fatores_segredo',
        'dois_fatores_codigos_recuperacao',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acesso_em' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
            'dois_fatores_segredo' => 'encrypted',
            'dois_fatores_codigos_recuperacao' => 'encrypted:array',
            'dois_fatores_confirmado_em' => 'datetime',
        ];
    }

    /**
     * Segundo fator ativo. Só vale depois de CONFIRMADO: segredo sem
     * confirmação é ativação em curso e não pode barrar o login.
     */
    public function doisFatoresAtivo(): bool
    {
        return $this->dois_fatores_confirmado_em !== null;
    }

    /**
     * TODA conta é obrigada a ter (middleware ExigirSegundoFator) — decisão
     * dele em 03/11/2026: o painel do site fica na internet aberta, sem muro
     * nem lista de IP, e o 2º fator é a porta. No Duo só o administrador era.
     */
    public function exigeDoisFatores(): bool
    {
        return true;
    }

    // ---------------------------------------------------------------- domínio

    public function administrador(): bool
    {
        return $this->hasRole(Perfis::ADMINISTRADOR);
    }

    /**
     * O nome do perfil principal, para a tela.
     *
     * Uma conta pode ter mais de um papel; a tela mostra o primeiro e a
     * listagem mostra todos. Nunca use isto para DECIDIR permissão — quem
     * decide é `can()`, e confundir papel com permissão é o erro que
     * PADROES.md §"Papel não é escopo" registra em três sistemas.
     */
    public function perfilPrincipal(): ?string
    {
        return Perfis::rotulo($this->roles->first()?->name);
    }

    public function registrarAcesso(): void
    {
        // `updateQuietly`: `ultimo_acesso_em` está na lista de campos ignorados
        // da trilha, mas o save normal ainda dispararia o observer a cada
        // requisição. Trilha de auditoria não é log de acesso.
        $this->forceFill(['ultimo_acesso_em' => now()])->saveQuietly();
    }

    // ---------------------------------------------------------------- escopos

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
