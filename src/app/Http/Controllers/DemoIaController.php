<?php

namespace App\Http\Controllers;

use App\Support\PergunteAosDados;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * A pergunta da demonstração "Pergunte aos dados". Só responde a quem passou
 * pelo formulário de lead nesta sessão (`DemoLeadController`), e a resposta é
 * sempre JSON com um `texto` para a página mostrar como texto puro.
 */
class DemoIaController extends Controller
{
    public function perguntar(Request $request, PergunteAosDados $ponte): JsonResponse
    {
        abort_unless(PergunteAosDados::configurada(), 404);

        $visitante = $request->session()->get('demo_visitante');
        $liberada = in_array(PergunteAosDados::DEMO, (array) $request->session()->get('demos_liberadas', []), true);

        if (! $liberada || ! is_string($visitante)) {
            return response()->json(['texto' => 'A sessão expirou. Recarregue a página e deixe o contato de novo.'], 403);
        }

        $dados = $request->validate([
            'pergunta' => 'required|string|min:3|max:280',
        ], [
            'pergunta.*' => 'Escreva uma pergunta de 3 a 280 caracteres.',
        ]);

        $r = $ponte->perguntar($visitante, trim($dados['pergunta']));

        $texto = match (true) {
            $r['status'] === 200 && $r['resposta'] !== null => $r['resposta'],
            $r['recusa'] === 'visitante' => 'Você chegou ao limite de perguntas de hoje nesta demonstração. Amanhã ela abre de novo, e por aqui a gente conversa sem limite: é só chamar.',
            $r['recusa'] === 'dia' => 'A demonstração atingiu o limite de perguntas de hoje, somando todos os visitantes. Ela volta amanhã.',
            default => 'A IA está fora do ar neste momento. Tente de novo em alguns minutos.',
        };

        return response()->json([
            'texto' => $texto,
            'respondeu' => $r['status'] === 200 && $r['resposta'] !== null,
            'restantes' => $r['restantes'],
        ], $r['status'] === 200 && $r['resposta'] === null ? 503 : $r['status']);
    }
}
