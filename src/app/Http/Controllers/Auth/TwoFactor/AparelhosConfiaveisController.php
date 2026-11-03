<?php

namespace App\Http\Controllers\Auth\TwoFactor;

use App\Http\Controllers\Controller;
use App\Services\Auth\AparelhosConfiaveis;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;

/**
 * Remover aparelho confiável, em Minha conta. "Confiar" sem porta de saída é
 * porta que não fecha: a remoção nasce na mesma rodada da confiança.
 */
class AparelhosConfiaveisController extends Controller
{
    public function destroy(Request $request, AparelhosConfiaveis $aparelhos, string $aparelho): RedirectResponse
    {
        $aparelhos->remover($request->user(), $aparelho);

        return back()->with('sucesso', 'Aparelho removido. Nele, o próximo acesso volta a pedir o código.');
    }

    public function destroyAll(Request $request, AparelhosConfiaveis $aparelhos): RedirectResponse
    {
        $aparelhos->removerTodos($request->user());

        // Inclusive o aparelho de onde o pedido saiu: "remover todos" é "não
        // confio em nenhum".
        Cookie::queue(Cookie::forget(AparelhosConfiaveis::COOKIE));

        return back()->with('sucesso', 'Todos os aparelhos foram removidos.');
    }
}
