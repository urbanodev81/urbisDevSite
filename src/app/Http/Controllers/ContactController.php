<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Support\Alertas\EmailFormatado;
use App\Support\Captcha\Altcha;

class ContactController extends Controller
{
    /**
     * Janela em que uma mensagem IDÊNTICA é considerada repetição.
     *
     * Cinco minutos cobre com folga o caso real (a pessoa clica de novo
     * porque o envio demorou) sem impedir alguém de escrever de novo mais
     * tarde para corrigir ou complementar o que mandou.
     */
    private const JANELA_REPETICAO = 300;

    public function store(Request $request, Altcha $captcha)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone' => 'required|string|max:15',
            'message' => 'required|string|min:50|max:5000',
            'consent' => 'required|accepted',
            'captcha_token' => 'required|string',
        ], [
            'captcha_token.required' => 'Aguarde a verificação de segurança terminar e envie de novo.',
        ]);

        // Altcha auto-hospedado desde 29/09/2026 (era o Turnstile da
        // Cloudflare) — ver App\Support\Captcha\Altcha.
        if (! $captcha->verificar($validated['captcha_token'])) {
            return back()->withErrors(['captcha_token' => 'Falha na verificação de segurança. Recarregue a página e tente novamente.'])->withInput();
        }

        /*
         * O MESMO RECADO NÃO É ENVIADO DUAS VEZES — 23/08/2026
         *
         * O defeito era real e foi REPRODUZIDO antes de existir correção: com
         * o envio demorando (o SMTP do Zoho é síncrono, dentro da requisição),
         * a pessoa não vê nada acontecer, clica de novo, e o segundo POST
         * chega enquanto o primeiro ainda está no ar. O navegador aborta a
         * primeira navegação — mas o servidor JÁ tinha mandado o e-mail.
         * Abortar navegação não desfaz o que o servidor fez. Medido: dois
         * cliques com 1,5 s entre eles = duas mensagens na caixa.
         *
         * O botão desabilitado da tela resolve o caso comum, e é ele que a
         * pessoa vê. NÃO é o que garante: sem JavaScript, com Enter no
         * teclado, ou com um POST repetido por qualquer outro motivo, ele não
         * existe. A garantia é esta, no servidor.
         *
         * A chave é o CONTEÚDO (e-mail + telefone + texto), não a sessão nem o
         * IP: o mesmo recado é o mesmo recado, e quem escrever coisa diferente
         * passa direto. `Cache::add()` é atômico — a segunda requisição perde
         * a corrida mesmo se as duas chegarem no mesmo instante.
         *
         * E responde SUCESSO, não erro: para quem escreveu, a mensagem foi
         * mesmo enviada. Dizer "falhou" faria a pessoa tentar uma terceira vez.
         */
        $chave = 'contato:'.hash('sha256', mb_strtolower($validated['email']).'|'.$validated['phone'].'|'.$validated['message']);

        if (! Cache::add($chave, true, self::JANELA_REPETICAO)) {
            Log::info('Contato repetido, não reenviado', [
                'email' => $validated['email'],
            ]);

            return back()->with('success', 'Mensagem enviada com sucesso! Entraremos em contato em breve.');
        }

        EmailFormatado::para((string) config('mail.from.address'))
            ->assunto('Contato do site — '.$validated['name'])
            ->titulo('Novo contato pelo site')
            ->campos([
                'Nome' => $validated['name'],
                'E-mail' => $validated['email'],
                'Telefone' => $validated['phone'],
            ])
            ->paragrafo($validated['message'])
            ->responderPara($validated['email'], $validated['name'])
            ->assinatura('Formulário de contato — urbisdev.tech')
            ->enviar();

        Log::info('Contato recebido', [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        return back()->with('success', 'Mensagem enviada com sucesso! Entraremos em contato em breve.');
    }
}
