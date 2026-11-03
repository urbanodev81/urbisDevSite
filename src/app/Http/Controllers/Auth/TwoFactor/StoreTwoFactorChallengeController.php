<?php

namespace App\Http\Controllers\Auth\TwoFactor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\AparelhosConfiaveis;
use App\Services\Auth\TotpService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

/**
 * Fecha o login: confere o segundo fator e só então autentica de verdade.
 * O mesmo campo aceita o código de 6 dígitos do aplicativo OU um código de
 * recuperação (uso único) — é o que a pessoa tem em mãos quando perdeu o
 * celular, e uma tela separada só atrapalharia nesse momento.
 */
class StoreTwoFactorChallengeController extends Controller
{
    /**
     * Teto por CONTA, qualquer que seja a origem (regra da casa, 29/09/2026).
     * O `throttle` da rota é por IP; sem este, quem tem a senha e vários
     * endereços varre os 10^6 códigos sem pressa.
     */
    public const MAX_ERROS = 5;

    public const DECAY_ERROS = 900;

    public function __invoke(Request $request, TotpService $totp, AparelhosConfiaveis $aparelhos): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'max:20'],
            // Confiar neste aparelho (10/10/2026): opt-in a cada login, e o
            // apelido é o que a pessoa vai ler depois na lista de Minha conta.
            'confiar' => ['sometimes', 'boolean'],
            'apelido' => ['nullable', 'required_if_accepted:confiar', 'string', 'max:60'],
        ], [
            'apelido.required_if_accepted' => 'Dê um apelido ao aparelho para confiar nele.',
        ]);

        $id = $request->session()->get('2fa.user_id');

        // Sem a marca de sessão ninguém provou a senha — 419 (sessão expirada)
        // em vez de 403: é literalmente o caso de a sessão do desafio ter
        // morrido entre a senha e o código.
        abort_if(! $id, 419);

        // Pelo provedor de autenticação, como o login faz: é lookup de
        // identidade, antes de existir usuário logado.
        $usuario = Auth::getProvider()->retrieveById($id);

        if (! $usuario instanceof User || ! $usuario->doisFatoresAtivo()) {
            $request->session()->forget(['2fa.user_id', '2fa.remember']);

            return redirect()->route('login');
        }

        $balde = 'dois-fatores:'.$usuario->id;

        if (RateLimiter::tooManyAttempts($balde, self::MAX_ERROS)) {
            // O desafio morre: a pessoa volta para a senha, e o balde segue
            // contando até esfriar.
            $request->session()->forget(['2fa.user_id', '2fa.remember']);

            return redirect()->route('login')->withErrors([
                'email' => trans('auth.throttle', [
                    'seconds' => RateLimiter::availableIn($balde),
                    'minutes' => ceil(RateLimiter::availableIn($balde) / 60),
                ]),
            ]);
        }

        $codigo = trim($request->string('code')->toString());

        if (! $totp->verify($usuario->dois_fatores_segredo, $codigo) && ! $this->gastarCodigoDeRecuperacao($usuario, $codigo)) {
            RateLimiter::hit($balde, self::DECAY_ERROS);

            throw ValidationException::withMessages([
                'code' => 'Código inválido ou já usado.',
            ]);
        }

        RateLimiter::clear($balde);

        $lembrar = (bool) $request->session()->get('2fa.remember', false);
        $request->session()->forget(['2fa.user_id', '2fa.remember']);

        Auth::login($usuario, $lembrar);

        $request->session()->regenerate();

        // Só depois de o código passar: a confiança nasce de um segundo
        // fator provado neste aparelho, nunca da senha sozinha.
        if ($request->boolean('confiar')) {
            $aparelhos->confiar($usuario, $request, $request->string('apelido')->toString());
        }

        return redirect()->intended(route('painel.inicio', absolute: false));
    }

    /**
     * Código de recuperação vale UMA vez: confere pelo hash e remove o usado.
     *
     * `saveQuietly` porque não há ninguém autenticado neste ponto: a trilha
     * de auditoria gravaria uma alteração sem autor, e o campo já é dos que
     * ela nunca registra.
     */
    private function gastarCodigoDeRecuperacao(User $usuario, string $codigo): bool
    {
        $codigos = $usuario->dois_fatores_codigos_recuperacao ?? [];

        foreach ($codigos as $indice => $hash) {
            if (Hash::check(mb_strtolower($codigo), $hash)) {
                unset($codigos[$indice]);
                $usuario->forceFill(['dois_fatores_codigos_recuperacao' => array_values($codigos)])->saveQuietly();

                return true;
            }
        }

        return false;
    }
}
