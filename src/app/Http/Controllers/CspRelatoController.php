<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Recebe o relato de violação da CSP em modo report-only (pendência 136,
 * 18/10/2026).
 *
 * A política do site ainda não barra nada: o navegador só conta o que TERIA
 * barrado, e conta aqui. O que fica em `storage/logs/csp-*.log` é a lista das
 * origens e dos scripts embutidos que a política definitiva precisa resolver
 * antes de virar `Content-Security-Policy` de verdade.
 *
 * O corpo vem de qualquer um na internet, então: lido com teto de tamanho,
 * só os campos conhecidos entram no log, cada um cortado, e a mesma violação
 * é registrada uma vez por dia — o log é uma lista, não um contador de visitas.
 * Responde 204 sempre: o navegador não faz nada com a resposta, e quem testa o
 * endereço não aprende nada com ela.
 */
class CspRelatoController extends Controller
{
    /** Um relato honesto tem menos de 2 KB; 8 KB é folga, não convite. */
    private const TETO_DO_CORPO = 8192;

    /**
     * Violações DISTINTAS registradas por dia. O site real tem meia dúzia; o
     * teto existe porque cada relato novo vira uma linha de log e um arquivo de
     * cache, e quem manda corpos variados de propósito encheria o disco.
     */
    private const TETO_POR_DIA = 500;

    /** Campos do formato `report-uri` (com hífen) e do `report-to` (camelCase). */
    private const CAMPOS = [
        'pagina' => ['document-uri', 'documentURL'],
        'diretiva' => ['effective-directive', 'effectiveDirective', 'violated-directive'],
        'bloqueado' => ['blocked-uri', 'blockedURL'],
        'arquivo' => ['source-file', 'sourceFile'],
        'linha' => ['line-number', 'lineNumber'],
        'trecho' => ['script-sample', 'sample'],
    ];

    public function __invoke(Request $request)
    {
        $corpo = $request->getContent();

        if ($corpo === '' || strlen($corpo) > self::TETO_DO_CORPO) {
            return response()->noContent();
        }

        $json = json_decode($corpo, true);
        // `report-uri` manda {"csp-report": {...}}; `report-to`, uma lista de
        // {"type": "csp-violation", "body": {...}}.
        $relato = $json['csp-report'] ?? ($json[0]['body'] ?? null);

        if (! is_array($relato)) {
            return response()->noContent();
        }

        $linha = [];
        foreach (self::CAMPOS as $nome => $chaves) {
            foreach ($chaves as $chave) {
                if (isset($relato[$chave]) && is_scalar($relato[$chave])) {
                    // Espaço em sequência vira um só: o trecho do script vem com
                    // quebra de linha, e o log é uma violação por linha.
                    $linha[$nome] = mb_substr(preg_replace('/\s+/u', ' ', (string) $relato[$chave]), 0, 300);
                    break;
                }
            }
        }

        // Extensão do navegador do visitante injeta o que quer na página; não
        // é assunto da política do site.
        if (preg_match('/^(chrome|moz|safari(-web)?)-extension/', $linha['bloqueado'] ?? '')) {
            return response()->noContent();
        }

        // Endereço sem a query: `?demo=`, `?utm_*` e afins são do visitante, e
        // multiplicariam a mesma violação por cada link de campanha.
        foreach (['pagina', 'arquivo', 'bloqueado'] as $campo) {
            if (isset($linha[$campo])) {
                $linha[$campo] = preg_replace('/[?#].*/s', '', $linha[$campo]);
            }
        }

        // A mesma violação é a mesma coisa em qualquer página: o script do tema
        // vem do layout e aparece em todas. Por isso a assinatura é o que foi
        // barrado e o começo dele (o `'report-sample'` da política), e não a
        // página nem a linha.
        $assinatura = 'csp-relato:'.sha1(implode('|', [
            $linha['diretiva'] ?? '', $linha['bloqueado'] ?? '', $linha['trecho'] ?? '',
        ]));

        $hoje = 'csp-relato:total:'.now()->format('Ymd');
        Cache::add($hoje, 0, now()->addDays(2));

        if (Cache::get($hoje) < self::TETO_POR_DIA && Cache::add($assinatura, true, now()->addDay())) {
            Cache::increment($hoje);
            Log::channel('csp')->info('violação', $linha);
        }

        return response()->noContent();
    }
}
