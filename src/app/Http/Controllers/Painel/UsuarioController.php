<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Mail\AvisoDoPainel;
use App\Models\User;
use App\Support\Perfis;
use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Throwable;

class UsuarioController extends Controller
{
    public function index(Request $request): Response
    {
        return Inertia::render('Painel/Usuarios/Index', [
            'usuarios' => User::query()->when($request->boolean('arquivados'), fn ($q) => $q->onlyTrashed())
                ->with('roles')->orderBy('name')->get()
                ->map(fn (User $u) => [
                    'uuid' => $u->uuid,
                    'nome' => $u->name,
                    'email' => $u->email,
                    'telefone' => $u->telefone,
                    'ativo' => $u->ativo,
                    'perfis' => $u->roles->map(fn ($r) => Perfis::rotulo($r->name))->all(),
                    'ultimoAcesso' => $u->ultimo_acesso_em?->format('d/m/Y H:i'),
                    'euMesmo' => $u->getKey() === $request->user()?->getKey(),
                ])->all(),
            // As abas Ativas / Arquivadas (no molde vinha das ações em lote).
            'arquivados' => $request->boolean('arquivados'),
            'totais' => [User::query()->count(), User::onlyTrashed()->count()],
            'podeGerenciar' => $request->user()?->can('usuarios.gerenciar') ?? false,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Painel/Usuarios/Formulario', ['perfis' => $this->perfis()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->regrasDoEmail(),
            'telefone' => ['nullable', 'string', 'max:20'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'perfis' => ['required', 'array', 'min:1'],
            'perfis.*' => [Rule::exists('roles', 'name')],
            'ativo' => ['boolean'],
        ]);

        $usuario = User::create([
            ...collect($dados)->except('perfis')->all(),
            // Conta criada por administrador já nasce verificada: não há
            // auto-cadastro aqui, então o e-mail não é uma afirmação de
            // estranho — é o endereço que alguém de dentro digitou.
            'email_verified_at' => now(),
        ]);

        $usuario->syncRoles($dados['perfis']);

        return redirect()->route('painel.usuarios.index')->with('sucesso', 'Conta criada.');
    }

    /**
     * Conta arquivada continua dona do e-mail.
     *
     * O índice único do banco não olha `deleted_at`: a regra `unique` sozinha
     * deixava passar o e-mail de uma conta arquivada e o INSERT estourava em
     * erro 500. A saída é restaurar a conta, e é isso que a mensagem diz.
     *
     * @return array<int, mixed>
     */
    private function regrasDoEmail(?User $usuario = null): array
    {
        return [
            'required', 'email', 'max:255',
            Rule::unique('users', 'email')->ignore($usuario?->id)->whereNull('deleted_at'),
            function (string $atributo, mixed $valor, Closure $falha) use ($usuario): void {
                $arquivada = User::onlyTrashed()->where('email', $valor)
                    ->when($usuario, fn ($q) => $q->whereKeyNot($usuario->getKey()))
                    ->exists();

                if ($arquivada) {
                    $falha('Já existe uma conta arquivada com este e-mail. Restaure-a na aba de arquivadas em vez de criar outra.');
                }
            },
        ];
    }

    public function edit(User $usuario): Response
    {
        return Inertia::render('Painel/Usuarios/Formulario', [
            'perfis' => $this->perfis(),
            'usuario' => [
                'uuid' => $usuario->uuid,
                'name' => $usuario->name,
                'email' => $usuario->email,
                'telefone' => $usuario->telefone,
                'ativo' => $usuario->ativo,
                'perfis' => $usuario->roles->pluck('name')->all(),
                'euMesmo' => $usuario->is(request()->user()),
                'doisFatoresAtivo' => $usuario->doisFatoresAtivo(),
            ],
        ]);
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $dados = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => $this->regrasDoEmail($usuario),
            'telefone' => ['nullable', 'string', 'max:20'],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'perfis' => ['required', 'array', 'min:1'],
            'perfis.*' => [Rule::exists('roles', 'name')],
            'ativo' => ['boolean'],
        ]);

        /*
         * Não dá para tirar o próprio acesso.
         *
         * Sem esta trava, o único administrador consegue se desativar ou
         * trocar o próprio perfil por um sem `usuarios.gerenciar` — e a partir
         * daí ninguém entra para desfazer. O conserto seria por tinker, no
         * banco. É barato impedir e caro descobrir.
         */
        if ($usuario->getKey() === $request->user()?->getKey()) {
            $dados['ativo'] = true;
            $dados['perfis'] = $usuario->roles->pluck('name')->all();
        }

        $usuario->update(collect($dados)->except(['perfis', 'password'])->all());

        if (filled($dados['password'] ?? null)) {
            $usuario->update(['password' => Hash::make($dados['password'])]);
        }

        $usuario->syncRoles($dados['perfis']);

        return redirect()->route('painel.usuarios.index')->with('sucesso', 'Conta atualizada.');
    }

    public function destroy(Request $request, User $usuario): RedirectResponse
    {
        abort_if($usuario->getKey() === $request->user()?->getKey(), 403,
            'Não é possível arquivar a própria conta por aqui.');

        // Soft delete: a conta perde o acesso e fica na aba Arquivadas.
        $usuario->delete();

        return redirect()->route('painel.usuarios.index')->with('sucesso', 'Conta arquivada. Ela está na aba Arquivadas.');
    }

    /**
     * Devolve uma conta arquivada (03/11/2026). No molde isto vinha pelas
     * ações em lote; aqui é um botão por conta. A trilha registra a volta.
     */
    public function restaurar(string $usuario): RedirectResponse
    {
        $conta = User::onlyTrashed()->where('uuid', $usuario)->firstOrFail();
        $conta->restore();

        return redirect()->route('painel.usuarios.index')->with('sucesso', "Conta de {$conta->name} restaurada.");
    }

    /**
     * Desligar o segundo fator de outra pessoa — 10/10/2026. É a saída de quem
     * perdeu o celular e os códigos de recuperação.
     *
     * Pede a senha de QUEM desliga: é rebaixar a proteção de uma conta, e uma
     * sessão esquecida aberta não pode fazer isso sozinha. Salvo pelo model,
     * para a trilha de auditoria registrar quem desligou o de quem. A própria
     * conta desliga em Minha conta.
     */
    public function desligarSegundoFator(Request $request, User $usuario): RedirectResponse
    {
        abort_if($usuario->is($request->user()), 403,
            'A própria conta desliga a verificação em Minha conta.');

        $request->validate([
            'current_password' => ['required', 'current_password'],
        ]);

        $estavaLigado = $usuario->doisFatoresAtivo();

        $usuario->forceFill([
            'dois_fatores_segredo' => null,
            'dois_fatores_codigos_recuperacao' => null,
            'dois_fatores_confirmado_em' => null,
        ])->save();

        /*
         * O dono da conta fica sabendo (10/10/2026). Sem isto, quem tomasse
         * uma conta de administrador rebaixaria a proteção de outra em
         * silêncio, e a vítima só descobriria ao notar que o login parou de
         * pedir o código. O e-mail diz o fato e quem fez, nada além.
         *
         * Falha de envio não desfaz o ato, que já aconteceu: vai para o log.
         */
        $avisada = false;

        if ($estavaLigado) {
            try {
                Mail::to($usuario->email)->send(new AvisoDoPainel(
                    assunto: 'A verificação em duas etapas da sua conta foi desligada',
                    corpo: "A verificação em duas etapas da sua conta foi desligada por {$request->user()->name}, em "
                        .now()->format('d/m/Y').' às '.now()->format('H:i').'. A partir de agora o acesso pede só a senha, '
                        .'e os aparelhos em que você tinha pedido para confiar foram removidos.'."\n\n"
                        .'Se você não pediu isso, troque a sua senha e avise quem administra o painel. '
                        .'Para ligar de novo, abra Minha conta.',
                    url: route('profile.edit'),
                ));
                $avisada = true;
            } catch (Throwable $e) {
                report($e);
            }
        }

        return back()->with('sucesso', "Verificação em duas etapas de {$usuario->name} desligada."
            .($avisada ? ' A conta foi avisada por e-mail.' : ''));
    }

    /** @return array<int, array{valor: string, rotulo: string, descricao: ?string}> */
    private function perfis(): array
    {
        return Role::query()->orderBy('name')->get()
            ->map(fn (Role $r) => [
                'valor' => $r->name,
                'rotulo' => Perfis::rotulo($r->name),
                'descricao' => Perfis::descricao($r->name),
            ])->all();
    }
}
