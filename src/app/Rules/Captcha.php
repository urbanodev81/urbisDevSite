<?php

declare(strict_types=1);

namespace App\Rules;

use App\Support\Captcha\Altcha;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * O captcha das telas de entrada do painel (03/11/2026, pendência 246): o
 * MESMO Altcha do formulário de contato (`App\Support\Captcha\Altcha`).
 *
 * `CAPTCHA_QA_BYPASS_TOKEN` é a porta do smoke de login pós-deploy (padrão
 * nascido no SSB): um valor fixo do `.env` que passa no lugar da prova de
 * trabalho, para o `curl` conseguir entrar depois de publicar. **Só fora de
 * produção** — em produção o valor é ignorado mesmo que exista no `.env`.
 */
class Captcha implements ValidationRule
{
    /** @return array<int, mixed> */
    public static function rules(): array
    {
        return ['required', 'string', new self];
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $token = (string) $value;
        $atalho = (string) config('services.captcha.qa_bypass_token');

        if ($atalho !== '' && ! app()->isProduction() && hash_equals($atalho, $token)) {
            return;
        }

        if (! app(Altcha::class)->verificar($token)) {
            $fail('A verificação de segurança falhou. Recarregue a página e tente de novo.');
        }
    }
}
