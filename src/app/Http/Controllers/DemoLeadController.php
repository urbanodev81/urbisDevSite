<?php

namespace App\Http\Controllers;

use App\Support\Alertas\EmailFormatado;
use App\Support\Captcha\Altcha;
use App\Support\PergunteAosDados;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * O formulário na frente de uma demonstração com `pede_contato` (decisão do
 * Ricardo, 11/10/2026): nome, e-mail e telefone, com o nosso captcha. Vira
 * lead na caixa da casa e libera a ferramenta para ESTA sessão.
 *
 * O site segue sem banco: o lead é o e-mail, e a liberação é uma marca na
 * sessão. Nada do que o visitante fizer depois (o arquivo que ele escolher)
 * passa por aqui. A exceção é a demonstração de IA, que também registra o
 * contato no Sócrates: é lá que as perguntas dela são contadas.
 */
class DemoLeadController extends Controller
{
    /** O mesmo lead não vira dois e-mails se a pessoa reenviar (ver ContactController). */
    private const JANELA_REPETICAO = 300;

    public function store(Request $request, Altcha $captcha, PergunteAosDados $ponte, string $slug)
    {
        $demo = config('demonstracoes.'.$slug);

        abort_unless(is_array($demo) && ($demo['pronta'] ?? false) && ($demo['pede_contato'] ?? false), 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:15',
            'consent' => 'required|accepted',
            'captcha_token' => 'required|string',
        ], [
            'captcha_token.required' => 'Aguarde a verificação de segurança terminar e envie de novo.',
        ]);

        if (! $captcha->verificar($validated['captcha_token'])) {
            return back()->withErrors(['captcha_token' => 'Falha na verificação de segurança. Recarregue a página e tente novamente.'])->withInput();
        }

        $chave = 'lead-demo:'.$slug.':'.hash('sha256', mb_strtolower($validated['email']).'|'.$validated['phone']);

        if (Cache::add($chave, true, self::JANELA_REPETICAO)) {
            EmailFormatado::para((string) config('mail.from.address'))
                ->assunto('Lead da demonstração — '.$demo['titulo'])
                ->titulo('Alguém abriu uma demonstração do site')
                ->campos([
                    'Demonstração' => $demo['titulo'],
                    'Nome' => $validated['name'],
                    'E-mail' => $validated['email'],
                    'Telefone' => $validated['phone'],
                ])
                ->paragrafo('Deixou o contato para usar a demonstração. O que a pessoa fez lá dentro não chega ao servidor.')
                ->responderPara($validated['email'], $validated['name'])
                ->assinatura('Demonstrações — urbisdev.tech')
                ->enviar();

            Log::info('Lead de demonstração', ['demo' => $slug, 'email' => $validated['email']]);
        }

        // A demonstração de IA conta as perguntas no Sócrates, por pessoa: o
        // contato é registrado lá antes de abrir. O e-mail acima já saiu, então
        // o lead não se perde se o Sócrates estiver fora; só a porta não abre.
        if ($slug === PergunteAosDados::DEMO) {
            $visitante = PergunteAosDados::visitante($validated['email'], $validated['phone']);

            if (! $ponte->registrarLead($visitante, $validated['name'], $validated['email'], $validated['phone'])) {
                return back()->withErrors(['captcha_token' => 'Recebemos o seu contato, mas a demonstração está fora do ar neste momento. Tente de novo em alguns minutos.'])->withInput();
            }

            $request->session()->put('demo_visitante', $visitante);
        }

        $liberadas = (array) $request->session()->get('demos_liberadas', []);
        $request->session()->put('demos_liberadas', array_values(array_unique([...$liberadas, $slug])));

        return redirect('/demonstracoes/'.$slug.'#ferramenta');
    }
}
