<?php

namespace App\Support\Captcha;

use AltchaOrg\Altcha\Altcha as AltchaLib;
use AltchaOrg\Altcha\ChallengeOptions;
use AltchaOrg\Altcha\Hasher\Algorithm;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Altcha auto-hospedado no formulário de contato — 29/09/2026.
 *
 * Até esta data o site era o único do ecossistema ainda no Cloudflare
 * Turnstile. Os sete sistemas migraram em 20/09; o site ficou por último.
 * Aqui é a versão ENXUTA do `AltchaVerifier` dos irmãos: sem driver, sem
 * provider, sem rollback para o Turnstile — o site tem um formulário só e o
 * valor dele é continuar pequeno (ver CLAUDE.md).
 *
 * O servidor emite o desafio assinado (`GET /captcha/desafio`), o navegador
 * gasta CPU resolvendo, e aqui se confere que a solução bate E que o desafio
 * saiu daqui — a assinatura HMAC é o que impede o cliente de inventar um
 * desafio barato.
 *
 * ## A chave HMAC sai do APP_KEY
 *
 * Os irmãos têm `CAPTCHA_HMAC_KEY` própria. Aqui a chave é DERIVADA do
 * `APP_KEY` (HMAC com um rótulo fixo), para o deploy não depender de alguém
 * criar um segredo novo no `.env` da VPS: o `APP_KEY` já está lá, é secreto e
 * sem ele o site nem sobe. Derivada, e não o `APP_KEY` cru, para que nada que
 * vaze do captcha seja a chave de criptografia da sessão.
 *
 * ## ⚠️ Pacote PHP 1.x casado com o widget 3.x
 *
 * O pacote 2.x emite o desafio no formato "v2" e o widget 3.x trava em
 * "verificando" para sempre, sem erro em lugar nenhum. Medido em navegador
 * real em 20/09/2026. Não "atualize" sem abrir o formulário e ver o widget
 * chegar a "Verificado".
 *
 * ## Anti-replay é nosso
 *
 * `verifySolution()` é pura: o mesmo payload verifica `true` até vencer. Cada
 * desafio aceito é queimado no cache com `Cache::add()` — o mesmo recurso que
 * o ContactController já usa contra o recado repetido.
 */
class Altcha
{
    /** Validade do desafio. Curta o bastante para limitar a janela de reuso. */
    private const EXPIRA_EM_SEGUNDOS = 600;

    /** Tentativas máximas do proof-of-work: fração de segundo num celular de entrada. */
    private const CUSTO = 50_000;

    public function criarDesafio(): array
    {
        $desafio = $this->altcha()->createChallenge(new ChallengeOptions(
            algorithm: Algorithm::SHA256,
            maxNumber: self::CUSTO,
            expires: new \DateTimeImmutable('+'.self::EXPIRA_EM_SEGUNDOS.' seconds'),
        ));

        return [
            'algorithm' => $desafio->algorithm,
            'challenge' => $desafio->challenge,
            'maxnumber' => $desafio->maxNumber,
            'salt' => $desafio->salt,
            'signature' => $desafio->signature,
        ];
    }

    public function verificar(string $token): bool
    {
        if ($token === '') {
            return false;
        }

        try {
            // Confere de uma vez: solução, assinatura nossa e validade.
            $valido = $this->altcha()->verifySolution($token, checkExpires: true);
        } catch (\Throwable) {
            // Payload que nem decodifica é lixo ou robô: reprova em silêncio.
            return false;
        }

        // Só queima DEPOIS de aceito: queimar antes deixaria um robô invalidar
        // desafios alheios mandando payload inválido com o salt de outro.
        return $valido && $this->queimar($token);
    }

    private function queimar(string $token): bool
    {
        $payload = json_decode((string) base64_decode($token, true), true);
        $desafio = $payload['challenge'] ?? null;

        if (! is_string($desafio) || $desafio === '') {
            return false;
        }

        $chave = 'captcha:usado:'.hash('sha256', $desafio);

        if (Cache::add($chave, true, self::EXPIRA_EM_SEGUNDOS)) {
            return true;
        }

        Log::warning('Captcha: desafio válido reapresentado (replay recusado).', ['chave' => $chave]);

        return false;
    }

    private function altcha(): AltchaLib
    {
        $appKey = (string) config('app.key');

        // Sem APP_KEY o desafio sairia assinado com chave vazia — qualquer um
        // forjaria. Estoura em vez de seguir: captcha desligado em silêncio se
        // comporta igualzinho a captcha que funciona.
        if ($appKey === '') {
            throw new \RuntimeException('APP_KEY vazio: o captcha não tem com que assinar o desafio.');
        }

        return new AltchaLib(hash_hmac('sha256', 'urbisdev-site:captcha', $appKey));
    }
}
