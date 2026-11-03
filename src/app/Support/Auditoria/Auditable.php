<?php

declare(strict_types=1);

namespace App\Support\Auditoria;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Trilha de alterações. Pendura nos eventos do Eloquent e grava quem mudou o
 * quê, de que para que.
 *
 * **`Auditable` e não `Auditavel`**: é o nome nos quatro irmãos que já têm a
 * trilha, e nome diferente para a mesma coisa é o defeito mais caro do
 * ecossistema — este repo acabou de pagar por ele duas vezes no mesmo dia
 * (`CartaoLateral`, `useTema`). O resto do arquivo é em português porque é o
 * vocabulário do domínio; o nome da peça compartilhada é o canônico.
 *
 * **Opt-in por model, de propósito.** Só carrega `use Auditable` o que importa
 * para rastreabilidade. Auditar tudo enche a tabela de ruído e esconde o que
 * interessa — nos irmãos são 3 a 6 models por sistema.
 *
 * Uma linha POR CAMPO alterado, e não um JSON de antes/depois como nos outros
 * quatro. A tabela daqui nasceu assim (`campo`, `de`, `para`), e é melhor para
 * a pergunta que a trilha responde na prática: "quem mexeu no valor deste
 * contrato?". Custa mais linhas; a resposta não exige ler JSON na mão.
 */
trait Auditable
{
    /**
     * Nunca sai no log, sem exceção e sem opt-out.
     *
     * A lista é incondicional e essa escolha é do Synapse e do Movana, contra
     * o desenho do SSB e do USI — que têm um `$auditSkip` declarável por model
     * e onde **nenhum model dos dois repos o usa**. Opt-out que depende de
     * alguém lembrar é opt-out que ninguém aplica.
     *
     * Aqui isso não é hipótese: `User` está entre os models auditados, e sem a
     * lista o hash da senha iria para o log a cada troca — na tabela que mais
     * gente tem permissão de ler.
     */
    private const CAMPOS_SENSIVEIS = [
        'password',
        'remember_token',
        'dois_fatores_segredo',
        'dois_fatores_codigos_recuperacao',
    ];

    /**
     * Campos que mudam sozinhos e não dizem nada a ninguém.
     *
     * Sem isto, cada `save()` gera uma linha de `updated_at` e a trilha vira
     * ruído puro — foi o primeiro problema em todos os irmãos.
     */
    private const CAMPOS_IGNORADOS = [
        'created_at',
        'updated_at',
        'ultimo_acesso_em',
    ];

    public static function bootAuditable(): void
    {
        static::created(fn (Model $m) => $m->registrarAuditoria('criado'));
        static::updated(fn (Model $m) => $m->registrarAuditoria('alterado'));
        static::deleted(fn (Model $m) => $m->registrarAuditoria('removido'));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $m) => $m->registrarAuditoria('restaurado'));
        }
    }

    public function auditorias(): MorphMany
    {
        return $this->morphMany(Auditoria::class, 'auditavel');
    }

    protected function registrarAuditoria(string $evento): void
    {
        $usuario = auth()->user();

        $comum = [
            'evento' => $evento,

            /*
             * `exists` e não só `id`: quando a conta apagada É a de quem está
             * agindo — o expurgo de um titular, ou alguém removendo a própria
             * conta —, o evento `removido` dispara DEPOIS do delete, e gravar
             * o id estouraria a chave estrangeira e derrubaria a operação
             * inteira. A trilha não pode ser o motivo de uma exclusão falhar.
             *
             * Não custa consulta: o Eloquent marca `exists = false` na
             * instância assim que o delete passa.
             */
            'user_id' => $usuario?->exists ? $usuario->getKey() : null,
            // Preservado como texto: a trilha precisa sobreviver à
            // anonimização do autor, senão o expurgo de um titular apaga a
            // prova de quem alterou o dado de OUTRO.
            'user_nome' => $usuario?->name,
            'ip' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255) ?: null,
            'ocorreu_em' => now(),
        ];

        if ($evento !== 'alterado') {
            Auditoria::create([...$comum, 'auditavel_type' => static::class, 'auditavel_id' => $this->getKey()]);

            return;
        }

        foreach ($this->mudancasAuditaveis() as $campo => [$de, $para]) {
            Auditoria::create([
                ...$comum,
                'auditavel_type' => static::class,
                'auditavel_id' => $this->getKey(),
                'campo' => $campo,
                'de' => $de,
                'para' => $para,
            ]);
        }
    }

    /**
     * O que de fato mudou, já sem o que não pode ou não deve ser registrado.
     *
     * @return array<string, array{0: ?string, 1: ?string}>
     */
    protected function mudancasAuditaveis(): array
    {
        $mudancas = [];

        foreach ($this->getChanges() as $campo => $novo) {
            if (in_array($campo, self::CAMPOS_IGNORADOS, strict: true)) {
                continue;
            }

            /*
             * Campo sensível gera linha, mas SEM valor. Registrar "a senha
             * mudou às 14h" é rastreabilidade; registrar o hash é vazamento.
             * Omitir a linha inteira seria perder o rastro da troca.
             */
            if (in_array($campo, self::CAMPOS_SENSIVEIS, strict: true)) {
                $mudancas[$campo] = [null, null];

                continue;
            }

            $mudancas[$campo] = [
                $this->paraTexto($this->getOriginal($campo)),
                $this->paraTexto($novo),
            ];
        }

        return $mudancas;
    }

    private function paraTexto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_bool($valor)) {
            return $valor ? 'sim' : 'não';
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }

        if (is_array($valor) || is_object($valor)) {
            return json_encode($valor, JSON_UNESCAPED_UNICODE);
        }

        return mb_substr((string) $valor, 0, 1000);
    }
}
