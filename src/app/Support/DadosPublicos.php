<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

/**
 * Dados públicos brasileiros para as demonstrações (20/10/2026): CEP (ViaCEP,
 * com a BrasilAPI de reserva), CNPJ e feriados nacionais (BrasilAPI).
 *
 * A consulta sai daqui e não do navegador por três razões: a CSP do site só
 * deixa a página falar com ela mesma; o cache serve a todos os visitantes e
 * poupa o limite por IP das APIs gratuitas; e o que volta é só o que a
 * demonstração mostra — nada do que o visitante digita é guardado.
 *
 * Toda falha vira `null`: a demonstração segue com o dado inventado, e o
 * visitante preenche à mão.
 */
class DadosPublicos
{
    private const BRASILAPI = 'https://brasilapi.com.br/api';

    /** Nacionais na BrasilAPI e facultativos na lei: não fecham a agenda. */
    private const FACULTATIVOS = '/carnaval|cinzas|corpus christi|p[aá]scoa/iu';

    /** @return array{logradouro: string, bairro: string, cidade: string, uf: string}|null */
    public function cep(string $cep): ?array
    {
        $cep = preg_replace('/\D/', '', $cep);
        if (strlen($cep) !== 8) {
            return null;
        }

        return $this->lembrar("cep.$cep", function () use ($cep) {
            $via = $this->json("https://viacep.com.br/ws/$cep/json/");
            if (is_array($via) && ! isset($via['erro']) && is_string($via['localidade'] ?? null)) {
                return $this->endereco($via['logradouro'] ?? '', $via['bairro'] ?? '', $via['localidade'], $via['uf'] ?? '');
            }

            $br = $this->json(self::BRASILAPI."/cep/v2/$cep");

            return is_array($br) && is_string($br['city'] ?? null)
                ? $this->endereco($br['street'] ?? '', $br['neighborhood'] ?? '', $br['city'], $br['state'] ?? '')
                : null;
        });
    }

    /**
     * Só o que a galeria veste: nome, cidade e o ramo que escolhe o tipo de
     * site. MEI e empresário individual têm o nome da PESSOA na razão social —
     * para eles só sai o nome fantasia, e sem fantasia, nome nenhum.
     *
     * @return array{nome: ?string, cidade: string, uf: string, tipo: ?string}|null
     */
    public function cnpj(string $cnpj): ?array
    {
        $cnpj = preg_replace('/\D/', '', $cnpj);
        if (! self::cnpjValido($cnpj)) {
            return null;
        }

        return $this->lembrar("cnpj.$cnpj", function () use ($cnpj) {
            $d = $this->json(self::BRASILAPI."/cnpj/v1/$cnpj");
            if (! is_array($d) || ! is_string($d['municipio'] ?? null)) {
                return null;
            }

            $fantasia = trim((string) ($d['nome_fantasia'] ?? ''));
            $pessoa = ($d['opcao_pelo_mei'] ?? false) === true
                || in_array((int) ($d['codigo_natureza_juridica'] ?? 0), [2135, 2305], true);
            $nome = $fantasia !== '' ? $fantasia : ($pessoa ? null : trim((string) ($d['razao_social'] ?? '')));

            return [
                'nome' => $nome !== null && $nome !== '' ? mb_convert_case(mb_strtolower(mb_substr($nome, 0, 32)), MB_CASE_TITLE) : null,
                'cidade' => mb_convert_case(mb_strtolower((string) $d['municipio']), MB_CASE_TITLE),
                'uf' => strtoupper(substr((string) ($d['uf'] ?? ''), 0, 2)),
                'tipo' => self::tipoPeloRamo((string) ($d['cnae_fiscal'] ?? '')),
            ];
        });
    }

    /** @return list<array{data: string, nome: string}> Ano atual e o seguinte, só os feriados por lei. */
    public function feriados(): array
    {
        $ano = (int) now()->year;

        return $this->lembrar("feriados.$ano", function () use ($ano) {
            $lista = [];
            foreach ([$ano, $ano + 1] as $a) {
                $resposta = $this->json(self::BRASILAPI."/feriados/v1/$a");
                if (! is_array($resposta)) {
                    return null;
                }
                foreach ($resposta as $f) {
                    if (is_array($f) && is_string($f['date'] ?? null) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $f['date'])
                        && is_string($f['name'] ?? null) && ! preg_match(self::FACULTATIVOS, $f['name'])) {
                        $lista[] = ['data' => $f['date'], 'nome' => mb_substr($f['name'], 0, 60)];
                    }
                }
            }

            return $lista;
        }) ?? [];
    }

    public static function cnpjValido(string $cnpj): bool
    {
        if (! preg_match('/^\d{14}$/', $cnpj) || preg_match('/^(\d)\1{13}$/', $cnpj)) {
            return false;
        }
        foreach ([12, 13] as $n) {
            $soma = 0;
            for ($i = 0, $peso = $n - 7; $i < $n; $i++, $peso = $peso === 2 ? 9 : $peso - 1) {
                $soma += (int) $cnpj[$i] * $peso;
            }
            if ((int) $cnpj[$n] !== (($soma % 11) < 2 ? 0 : 11 - $soma % 11)) {
                return false;
            }
        }

        return true;
    }

    /** O CNAE principal escolhe qual dos quatro sites de exemplo se veste. */
    private static function tipoPeloRamo(string $cnae): ?string
    {
        return match (true) {
            str_starts_with($cnae, '452') => 'oficina',
            str_starts_with($cnae, '900') => 'banda',
            str_starts_with($cnae, '86') => 'clinica',
            str_starts_with($cnae, '682') => 'imobiliaria',
            default => null,
        };
    }

    private function endereco(string $rua, string $bairro, string $cidade, string $uf): array
    {
        return ['logradouro' => mb_substr($rua, 0, 80), 'bairro' => mb_substr($bairro, 0, 60), 'cidade' => mb_substr($cidade, 0, 60), 'uf' => strtoupper(substr($uf, 0, 2))];
    }

    /** Acerto fica um dia; falha fica dez minutos, para não martelar a API caída. */
    private function lembrar(string $chave, callable $buscar): ?array
    {
        $chave = 'dados-publicos.'.$chave;
        $guardado = Cache::get($chave);
        if ($guardado !== null) {
            return $guardado === false ? null : $guardado;
        }

        $valor = $buscar();
        Cache::put($chave, $valor ?? false, $valor === null ? now()->addMinutes(10) : now()->addDay());

        return $valor;
    }

    private function json(string $url): mixed
    {
        try {
            $r = Http::timeout(4)->connectTimeout(2)->acceptJson()->get($url);

            return $r->successful() ? $r->json() : null;
        } catch (Throwable) {
            return null;
        }
    }
}
