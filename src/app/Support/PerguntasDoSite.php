<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Pergunta;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * As perguntas de "Como trabalhamos" (bloco 4d da pendência 246, 08/11/2026).
 *
 * Eram 7 linhas fixas no `como-trabalhamos.blade.php`. Agora a página lê
 * `lista()`, e quem mexe é a tela Site › Perguntas. **Sem `FAQPage`** no
 * JSON-LD, decisão dele de 06/11: são perguntas para o visitante fazer a
 * qualquer fornecedor, não perguntas frequentes sobre a casa.
 *
 * **Banco fora → `PADRAO`** (as 7 de 08/11), sem guardar cache, para a próxima
 * requisição tentar de novo (`SitePublicoSemBancoTest`). Nenhuma ativa é
 * escolha de quem editou: a seção some da página.
 */
final class PerguntasDoSite
{
    private const CHAVE_CACHE = 'perguntas.site';

    /** @var list<array{pergunta: string, explicacao: ?string}>|null memo da requisição */
    private static ?array $memo = null;

    /**
     * As 7 de 08/11/2026, palavra por palavra. É a semente da migration e a
     * reserva com o banco fora.
     *
     * @var list<array{pergunta: string, explicacao: string}>
     */
    public const PADRAO = [
        ['pergunta' => 'Como vocês descobrem que o sistema caiu?', 'explicacao' => 'Se a resposta for “o cliente avisa”, o custo da queda é seu.'],
        ['pergunta' => 'O que acontece se a pessoa que fez isto sair amanhã?', 'explicacao' => 'A resposta está na documentação, não na confiança.'],
        ['pergunta' => 'Onde está escrito por que essa decisão foi tomada?', 'explicacao' => 'O código conta o quê; quase nunca conta o porquê.'],
        ['pergunta' => 'Quais dados pessoais o sistema guarda, e por quanto tempo?', 'explicacao' => 'Dado sem prazo é dado guardado para sempre por omissão.'],
        ['pergunta' => 'Como se prova que um cliente não enxerga o dado do outro?', 'explicacao' => '“O sistema foi feito assim” não é prova. Teste é.'],
        ['pergunta' => 'A tela funciona para quem enxerga pouco?', 'explicacao' => 'Acessibilidade depois da entrega custa a reescrita da interface.'],
        ['pergunta' => 'Se eu quiser trocar de fornecedor, o que sai comigo?', 'explicacao' => 'Código, dados e documentação — ou dependência.'],
    ];

    private const POR_EXTENSO = [1 => 'Uma', 'Duas', 'Três', 'Quatro', 'Cinco', 'Seis', 'Sete', 'Oito', 'Nove', 'Dez', 'Onze', 'Doze'];

    /** @return list<array{pergunta: string, explicacao: ?string}> as ativas, na ordem */
    public static function lista(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }

        try {
            return self::$memo = Cache::rememberForever(self::CHAVE_CACHE, fn () => Pergunta::query()
                ->where('ativo', true)->orderBy('ordem')->orderBy('id')
                ->get(['pergunta', 'explicacao'])
                ->map(fn (Pergunta $p) => $p->only(['pergunta', 'explicacao']))
                ->all());
        } catch (Throwable $e) {
            report($e);

            return self::PADRAO;
        }
    }

    public static function esquecer(): void
    {
        self::$memo = null;
        Cache::forget(self::CHAVE_CACHE);
    }

    /** "Sete perguntas", "Uma pergunta", "13 perguntas": o título acompanha a lista. */
    public static function quantasPorExtenso(int $n): string
    {
        return (self::POR_EXTENSO[$n] ?? (string) $n).($n === 1 ? ' pergunta' : ' perguntas');
    }
}
