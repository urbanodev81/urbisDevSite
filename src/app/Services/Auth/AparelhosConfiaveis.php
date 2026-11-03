<?php

declare(strict_types=1);

namespace App\Services\Auth;

use App\Models\AparelhoConfiavel;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Str;

/**
 * "Confiar neste aparelho" (decisão do Ricardo, 10/10/2026). Molde do
 * `DispositivosConfiaveis` do Sócrates, com o apelido que ele pediu; o mesmo
 * arquivo do BHD.
 *
 * REGRA QUE NÃO PODE SER QUEBRADA: o aparelho confiável é, no máximo, o
 * SEGUNDO fator. Não existe caminho neste arquivo que autentique alguém: quem
 * diz quem é a pessoa continua sendo a senha, e o cookie só dispensa o código.
 */
class AparelhosConfiaveis
{
    /**
     * 30 dias contados de quando a pessoa confiou, sem deslizar. O Sócrates
     * desliza 60 a cada uso; aqui o prazo é fixo para o código voltar a ser
     * pedido pelo menos uma vez por mês em todo aparelho.
     */
    public const DIAS = 30;

    public const COOKIE = 'duo_aparelho';

    /**
     * Este aparelho é confiável PARA ESTA PESSOA?
     *
     * O `user_id` na consulta é a peça de segurança: sem ele, o cookie válido
     * de uma conta dispensaria o código de outra no mesmo navegador.
     */
    public function reconhecer(User $usuario, Request $request): bool
    {
        $token = $request->cookie(self::COOKIE);

        if (! is_string($token) || $token === '') {
            return false;
        }

        $aparelho = AparelhoConfiavel::query()
            ->where('user_id', $usuario->id)
            ->where('token_hash', hash('sha256', $token))
            ->vigente()
            ->first();

        if ($aparelho === null) {
            return false;
        }

        $aparelho->forceFill(['ultimo_uso_em' => now(), 'ip' => $request->ip()])->save();

        return true;
    }

    /**
     * Passa a confiar neste aparelho e deixa o cookie na resposta. O segredo
     * em claro só existe aqui e no navegador; o banco guarda o sha256.
     */
    public function confiar(User $usuario, Request $request, string $apelido): AparelhoConfiavel
    {
        $token = Str::random(64);

        $aparelho = AparelhoConfiavel::create([
            'user_id' => $usuario->id,
            'token_hash' => hash('sha256', $token),
            'apelido' => Str::limit(trim($apelido), 60, ''),
            'descricao' => self::descrever($request->userAgent()),
            'ip' => $request->ip(),
            'ultimo_uso_em' => now(),
            'expira_em' => now()->addDays(self::DIAS),
        ]);

        // `httpOnly` impede script de ler o segredo; `secure` acompanha a
        // sessão; `lax` basta para ele viajar no POST do próprio login e não
        // em requisição de terceiro.
        Cookie::queue(Cookie::make(
            name: self::COOKIE,
            value: $token,
            minutes: self::DIAS * 24 * 60,
            path: '/',
            domain: null,
            secure: (bool) config('session.secure'),
            httpOnly: true,
            raw: false,
            sameSite: 'lax',
        ));

        return $aparelho;
    }

    /** @return Collection<int, AparelhoConfiavel> */
    public function listar(User $usuario): Collection
    {
        return AparelhoConfiavel::query()
            ->where('user_id', $usuario->id)
            ->vigente()
            ->orderByDesc('ultimo_uso_em')
            ->get();
    }

    /** O aparelho de onde este pedido saiu, se for um dos confiáveis da pessoa. */
    public function atual(User $usuario, Request $request): ?string
    {
        $token = $request->cookie(self::COOKIE);

        if (! is_string($token) || $token === '') {
            return null;
        }

        return AparelhoConfiavel::query()
            ->where('user_id', $usuario->id)
            ->where('token_hash', hash('sha256', $token))
            ->vigente()
            ->value('uuid');
    }

    /** Escopo pelo dono dentro do serviço: uuid de aparelho alheio não remove nada. */
    public function remover(User $usuario, string $uuid): bool
    {
        return AparelhoConfiavel::query()
            ->where('user_id', $usuario->id)
            ->where('uuid', $uuid)
            ->delete() > 0;
    }

    /**
     * Derruba todos. Chamado pela tela e, sem passar por tela nenhuma, por
     * toda troca de senha e todo desligamento do segundo fator: quem trocou a
     * senha por desconfiança não pode continuar com aparelho confiável de
     * antes, e segredo novo não pode herdar a confiança dada ao antigo.
     */
    public function removerTodos(User $usuario): int
    {
        return AparelhoConfiavel::query()->where('user_id', $usuario->id)->delete();
    }

    /**
     * "Chrome no Windows", para a pessoa reconhecer a linha na lista e para
     * sugerir o apelido. Lista fechada: User-Agent é texto de quem pede, e
     * daqui só sai palavra que está escrita neste arquivo.
     */
    public static function descrever(?string $userAgent): string
    {
        $ua = (string) $userAgent;

        $navegador = match (true) {
            str_contains($ua, 'Edg/') => 'Edge',
            str_contains($ua, 'OPR/') => 'Opera',
            str_contains($ua, 'Firefox/') => 'Firefox',
            str_contains($ua, 'Chrome/') => 'Chrome',
            str_contains($ua, 'Safari/') => 'Safari',
            default => 'Navegador',
        };

        $sistema = match (true) {
            str_contains($ua, 'Android') => 'Android',
            str_contains($ua, 'iPhone') => 'iPhone',
            str_contains($ua, 'iPad') => 'iPad',
            str_contains($ua, 'Windows') => 'Windows',
            str_contains($ua, 'Mac OS X') => 'Mac',
            str_contains($ua, 'Linux') => 'Linux',
            default => null,
        };

        return $sistema === null ? $navegador : "{$navegador} no {$sistema}";
    }
}
