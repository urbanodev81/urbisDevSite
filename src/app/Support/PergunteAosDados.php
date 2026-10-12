<?php

namespace App\Support;

use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * A ponte da demonstração "Pergunte aos dados" com o Sócrates (pendência 203).
 *
 * O site continua sem banco e sem chave de IA: o lead, a contagem das
 * perguntas e a chamada ao modelo são do Sócrates. Daqui vai o contato (uma
 * vez), e a cada pergunta vão o texto do visitante e a tabela, que é sempre a
 * MESMA e sai de um arquivo nosso (`resources/demos/oficina-resumo.json`): o
 * visitante não escolhe o que a IA lê.
 */
class PergunteAosDados
{
    public const DEMO = 'pergunte';

    public static function configurada(): bool
    {
        return filled(config('demo_ia.url')) && filled(config('demo_ia.token'));
    }

    /**
     * Quem é este visitante para a contagem do Sócrates. Sai do contato, com a
     * chave da aplicação: a mesma pessoa cai no mesmo teto em outro aparelho,
     * e o Sócrates não precisa do e-mail para contar.
     */
    public static function visitante(string $email, string $telefone): string
    {
        return hash_hmac('sha256', mb_strtolower(trim($email)).'|'.preg_replace('/\D/', '', $telefone), (string) config('app.key'));
    }

    /** @return array<string, mixed> O ano de vendas da oficina, já resumido. */
    public static function dados(): array
    {
        return json_decode((string) file_get_contents(resource_path('demos/oficina-resumo.json')), true);
    }

    /** A tabela como a IA recebe: texto, uma linha por mês, serviço ou pessoa. */
    public static function contexto(): string
    {
        $d = self::dados();
        $meses = implode(';', $d['meses']);
        $linhas = [
            'Oficina Modelo (fictícia). Vendas dos últimos doze meses, de novembro a outubro, em ordem. Valores em reais.',
            '', 'POR MÊS', 'mes;faturamento;meta;vendas',
        ];
        foreach ($d['mensal'] as $m) {
            $linhas[] = "{$m['mes']};{$m['faturamento']};{$m['meta']};{$m['vendas']}";
        }
        foreach (['servicos' => 'SERVIÇO', 'vendedores' => 'VENDEDOR'] as $chave => $titulo) {
            foreach (['faturamento' => 'FATURAMENTO', 'vendas' => 'NÚMERO DE VENDAS'] as $medida => $rotulo) {
                array_push($linhas, '', "{$rotulo} POR {$titulo} E MÊS", "nome;{$meses};total");
                foreach ($d[$chave] as $item) {
                    $linhas[] = $item['nome'].';'.implode(';', $item[$medida]).';'.array_sum($item[$medida]);
                }
            }
        }
        array_push($linhas, '', 'POR DIA DA SEMANA (ano inteiro; a oficina não abre no domingo)', 'dia;faturamento;vendas');
        foreach ($d['dias'] as $dia) {
            $linhas[] = "{$dia['dia']};{$dia['faturamento']};{$dia['vendas']}";
        }

        return implode("\n", $linhas);
    }

    public function registrarLead(string $visitante, string $nome, string $email, string $telefone): bool
    {
        $resposta = $this->enviar('leads', [
            'demo' => self::DEMO,
            'visitante' => $visitante,
            'nome' => $nome,
            'email' => $email,
            'telefone' => $telefone,
        ], 8);

        return $resposta?->successful() ?? false;
    }

    /**
     * @return array{status: int, resposta: ?string, recusa: ?string, restantes: ?int}
     */
    public function perguntar(string $visitante, string $pergunta): array
    {
        $resposta = $this->enviar('perguntas', [
            'demo' => self::DEMO,
            'visitante' => $visitante,
            'pergunta' => $pergunta,
            'contexto' => self::contexto(),
        ], 40);

        $corpo = $resposta?->json();

        // Só três respostas do Sócrates têm significado para o visitante; o
        // resto (401 de token, 422, 5xx, sem resposta) é "fora do ar".
        if (! $resposta || ! is_array($corpo) || ! in_array($resposta->status(), [200, 429], true)) {
            return ['status' => 503, 'resposta' => null, 'recusa' => 'indisponivel', 'restantes' => null];
        }

        return [
            'status' => $resposta->status(),
            'resposta' => is_string($corpo['resposta'] ?? null) ? mb_substr($corpo['resposta'], 0, 1500) : null,
            'recusa' => in_array($corpo['recusa'] ?? null, ['visitante', 'dia'], true) ? $corpo['recusa'] : null,
            'restantes' => is_int($corpo['restantes'] ?? null) ? max(0, $corpo['restantes']) : null,
        ];
    }

    /** @param array<string, string> $corpo */
    private function enviar(string $caminho, array $corpo, int $teto): ?Response
    {
        if (! self::configurada()) {
            return null;
        }

        try {
            $resposta = Http::withToken((string) config('demo_ia.token'))
                ->acceptJson()
                ->connectTimeout(4)
                ->timeout($teto)
                ->post(rtrim((string) config('demo_ia.url'), '/').'/'.$caminho, $corpo);
        } catch (Throwable $e) {
            Log::warning('Pergunte aos dados: o Sócrates não respondeu', ['caminho' => $caminho, 'erro' => $e->getMessage()]);

            return null;
        }

        if ($resposta->failed() && $resposta->status() !== 429) {
            Log::warning('Pergunte aos dados: o Sócrates recusou', ['caminho' => $caminho, 'status' => $resposta->status()]);
        }

        return $resposta;
    }
}
