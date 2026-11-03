<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Rules\Captcha;
use App\Services\Auth\AparelhosConfiaveis;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Teto por CONTA, qualquer que seja a origem: trocar de IP não contorna.
     * Regra da casa (29/09/2026): 5 tentativas de senha, nunca 10.
     */
    public const MAX_POR_IDENTIDADE = 5;

    public const DECAY_IDENTIDADE = 900;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
            /*
             * O captcha, desde 20/09/2026. Este login era a porta mais aberta
             * do repo: sem captcha, sem honeypot, só o limite por tentativas
             * logo abaixo — e limite segura repetição, não a primeira
             * tentativa de uma lista de credenciais vazada.
             */
            'captcha_token' => Captcha::rules(),
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        /*
         * Senha certa não basta quando há segundo fator.
         *
         * `attemptWhen` em vez de `attempt` + `logout` (o desenho do Sócrates):
         * a função só roda com a senha já conferida, e recusar por ela não
         * autentica ninguém — então não nasce sessão, não dispara o evento
         * `Login` (que aqui grava a linha de acesso no Sócrates) e o
         * `remember_token` não gira. Entre as duas etapas existe só a marca de
         * sessão dizendo QUEM passou pela senha; quem conclui é o
         * StoreTwoFactorChallengeController. Mesmo desenho do BHD.
         */
        $aguardaCodigo = null;

        $entrou = Auth::attemptWhen(
            $this->only('email', 'password'),
            function (User $usuario) use (&$aguardaCodigo): bool {
                // Conta desativada não entra (03/11/2026). O molde mostrava
                // "Inativa" na tela de contas, mas o login não conferia — a
                // conta seguia entrando. Recusa com a MESMA mensagem da senha
                // errada: não confirma a quem tenta que a conta existe.
                if (! $usuario->ativo) {
                    return false;
                }

                // Aparelho em que a pessoa pediu para confiar (10/10/2026): a
                // senha acabou de ser conferida, e o cookie só dispensa o
                // código. Sem senha certa este trecho nem roda.
                if ($usuario->doisFatoresAtivo() && ! app(AparelhosConfiaveis::class)->reconhecer($usuario, $this)) {
                    $aguardaCodigo = $usuario;

                    return false;
                }

                return true;
            },
            $this->boolean('remember'),
        );

        if ($aguardaCodigo !== null) {
            RateLimiter::clear($this->throttleKey());
            RateLimiter::clear($this->throttleKeyPorIdentidade());

            $this->session()->put('2fa.user_id', $aguardaCodigo->id);
            $this->session()->put('2fa.remember', $this->boolean('remember'));

            return;
        }

        if (! $entrou) {
            RateLimiter::hit($this->throttleKey());
            RateLimiter::hit($this->throttleKeyPorIdentidade(), self::DECAY_IDENTIDADE);

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        RateLimiter::clear($this->throttleKey());
        RateLimiter::clear($this->throttleKeyPorIdentidade());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $porIp = RateLimiter::tooManyAttempts($this->throttleKey(), 5);
        $porIdentidade = RateLimiter::tooManyAttempts($this->throttleKeyPorIdentidade(), self::MAX_POR_IDENTIDADE);

        if (! $porIp && ! $porIdentidade) {
            return;
        }

        event(new Lockout($this));

        $seconds = max(
            $porIp ? RateLimiter::availableIn($this->throttleKey()) : 0,
            $porIdentidade ? RateLimiter::availableIn($this->throttleKeyPorIdentidade()) : 0,
        );

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }

    /**
     * A conta, sem a origem. O por-IP sozinho deixava quem tem vários
     * endereços seguir tentando a mesma conta (molde do USI, 29/09/2026).
     */
    public function throttleKeyPorIdentidade(): string
    {
        return 'login-id:'.Str::transliterate(Str::lower($this->string('email')));
    }
}
