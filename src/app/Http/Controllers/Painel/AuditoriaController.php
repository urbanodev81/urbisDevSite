<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Models\Auditoria;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A trilha de alterações, só de leitura.
 *
 * Não há ação nenhuma nesta tela, e isso é a definição da coisa: trilha que a
 * aplicação sabe editar não serve de trilha. O único caminho de saída daqui é
 * o expurgo por retenção (`lgpd:expurgo`).
 */
class AuditoriaController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $linhas = Auditoria::query()
            ->with('autor')
            ->when($request->filled('tipo'), fn ($q) => $q->where('auditavel_type', $request->string('tipo')))
            ->when($request->filled('usuario'), fn ($q) => $q->where('user_id', $request->integer('usuario')))
            ->orderByDesc('ocorreu_em')
            ->paginate(30)
            ->withQueryString()
            ->through(fn (Auditoria $a) => [
                'id' => $a->id,
                'tipo' => $a->tipoLegivel(),
                'registro' => $a->auditavel_id,
                'evento' => $a->evento,
                'campo' => $a->campo,
                'de' => $a->de,
                'para' => $a->para,
                'autor' => $a->nomeDoAutor(),
                'quando' => $a->ocorreu_em->format('d/m/Y H:i:s'),
                'ip' => $a->ip,
            ]);

        return Inertia::render('Painel/Auditoria/Index', [
            'linhas' => $linhas,
            'filtros' => $request->only(['tipo', 'usuario']),
            'tipos' => Auditoria::query()->distinct()->pluck('auditavel_type')
                ->map(fn ($t) => ['valor' => $t, 'rotulo' => class_basename((string) $t)])->all(),
            'usuarios' => User::query()->orderBy('name')->get(['id', 'name'])
                ->map(fn (User $u) => ['valor' => $u->id, 'rotulo' => $u->name])->all(),
        ]);
    }
}
