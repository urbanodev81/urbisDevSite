<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schedule;

/*
|--------------------------------------------------------------------------
| Tarefas agendadas (03/11/2026, pendência 246)
|--------------------------------------------------------------------------
|
| Quem roda é o contêiner `scheduler` (`php artisan schedule:work`). Como no
| molde "site pequeno", ele também DRENA A FILA a cada minuto: um worker de
| longa duração sai no `--max-time` e morre calado se o `restart:` falhar
| (foi assim que o BHD perdeu 43 avisos em 08/08/2026).
|
| Bloco 2 (04/11/2026): a entrega da outbox ao Sócrates, de minuto em minuto
| (o caminho de recuperação; no dia normal o formulário já entrega na hora),
| e o expurgo LGPD dos contatos e da trilha, de madrugada.
*/

Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('socrates:entregar-outbox')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('lgpd:expurgo')
    ->dailyAt('03:40')
    ->withoutOverlapping();
