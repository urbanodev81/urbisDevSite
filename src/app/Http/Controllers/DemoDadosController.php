<?php

namespace App\Http\Controllers;

use App\Support\DadosPublicos;
use Illuminate\Http\JsonResponse;

/**
 * As três consultas de dado público das demonstrações (Galeria de sites e
 * Agendamento). GET, sem sessão a guardar, e com `throttle` próprio na rota:
 * cada uma pode virar chamada a uma API de fora.
 */
class DemoDadosController extends Controller
{
    public function cep(string $cep, DadosPublicos $dados): JsonResponse
    {
        $endereco = $dados->cep($cep);

        return $endereco === null
            ? response()->json(['erro' => 'CEP não encontrado.'], 404)
            : response()->json($endereco)->header('Cache-Control', 'public, max-age=86400');
    }

    public function cnpj(string $cnpj, DadosPublicos $dados): JsonResponse
    {
        if (! DadosPublicos::cnpjValido(preg_replace('/\D/', '', $cnpj))) {
            return response()->json(['erro' => 'CNPJ inválido.'], 422);
        }

        $empresa = $dados->cnpj($cnpj);

        return $empresa === null
            ? response()->json(['erro' => 'Não encontramos esse CNPJ agora.'], 404)
            : response()->json($empresa)->header('Cache-Control', 'private, max-age=3600');
    }

    public function feriados(DadosPublicos $dados): JsonResponse
    {
        return response()->json($dados->feriados())->header('Cache-Control', 'public, max-age=86400');
    }
}
