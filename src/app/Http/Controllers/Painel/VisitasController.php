<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Support\VisitasDoSite;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Visitas do site (09/11/2026, pendência 263): os últimos 30 dias, dia a dia,
 * e as páginas mais abertas. Os números vêm do pacote urbanodev/visitas — sem
 * cookie, sem IP guardado, sem robô, sem quem está logado.
 */
class VisitasController extends Controller
{
    public function __invoke(VisitasDoSite $visitas): Response
    {
        return Inertia::render('Painel/Visitas', $visitas->paraOPainel());
    }
}
