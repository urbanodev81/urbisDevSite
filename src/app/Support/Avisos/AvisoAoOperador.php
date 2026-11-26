<?php

declare(strict_types=1);

namespace App\Support\Avisos;

use App\Jobs\Socrates\EntregarOutboxDoSocratesJob;
use App\Services\Socrates\OutboxDoSocrates;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Conta ao OPERADOR (nós) o que aconteceu no site, pelo Sócrates — 284 F3.
 *
 * Até 21/11/2026 o alerta de erro e de segurança do urbisdev era só e-mail.
 * Agora o fato vai também à outbox (`evento.relatado` → `POST /api/eventos`,
 * origem `nosso`), e o Sócrates decide, pela matriz do operador, se vira push
 * no PWA dele, Telegram ou linha do resumo. Molde: WJ (F2).
 *
 * Três regras que este arquivo segura:
 *
 * - **Nunca lança e nunca reporta.** É chamado de dentro do `reportable` do
 *   500: se a gravação falhar (o banco é justamente o que caiu) e o `rescue`
 *   reportasse, o erro do aviso viraria outro aviso, em laço.
 * - **Corpo magro.** Título e resumo curtos, nada de e-mail ou telefone de
 *   quem escreveu pelo site (LGPD, ADR 0006).
 * - **Só grava.** A entrega é do job e do agendador; Sócrates fora do ar não
 *   segura a requisição de ninguém, e a outbox tenta por 72 h.
 */
final class AvisoAoOperador
{
    public static function relatar(
        EventoDoSistema $evento,
        string $titulo,
        ?string $resumo = null,
        ?string $painelUrl = null,
    ): void {
        if (! $evento->paraOperador()) {
            return;
        }

        try {
            $registrado = app(OutboxDoSocrates::class)->registrar(OutboxDoSocrates::EVENTO_RELATADO, array_filter([
                'chave' => $evento->value,
                'origem' => (string) config('socrates.origem', 'nosso'),
                'severidade' => $evento->severidade(),
                'titulo' => mb_strimwidth(self::semContato($titulo), 0, 120, '…'),
                'resumo' => $resumo !== null ? mb_strimwidth(self::semContato($resumo), 0, 500, '…') : null,
                'painel_url' => $painelUrl,
                'ocorreu_em' => now()->toIso8601String(),
            ], fn ($v) => $v !== null && $v !== ''));

            if ($registrado !== null) {
                EntregarOutboxDoSocratesJob::dispatch();
            }
        } catch (Throwable $e) {
            Log::error('[avisos] não consegui registrar o aviso ao operador: '.$e->getMessage(), ['chave' => $evento->value]);
        }
    }

    /**
     * Rede de segurança para texto que não escrevemos (mensagem de exceção
     * com o SQL e os valores dentro): e-mail inteiro e sequência de 10+
     * dígitos (telefone, CPF) saem antes de ir ao Sócrates. O e-mail já
     *
     * mascarado (`r***@x.com`) passa, porque o `*` não casa.
     */
    public static function semContato(string $texto): string
    {
        $texto = preg_replace('/[\w.+-]+@[\w-]+(\.[\w-]+)+/u', '[e-mail]', $texto) ?? $texto;

        return preg_replace('/\(?(?<!\d)(?:\d[\s().-]{0,2}){10,}(?!\d)/', '[número]', $texto) ?? $texto;
    }

    /** `ricardo@x.com` → `r***@x.com`. Diz QUAL conta sem pôr o endereço inteiro no Telegram. */
    public static function mascarar(?string $email): ?string
    {
        if (! is_string($email) || ! str_contains($email, '@')) {
            return null;
        }

        [$nome, $dominio] = explode('@', $email, 2);

        return mb_substr($nome, 0, 1).'***@'.$dominio;
    }
}
