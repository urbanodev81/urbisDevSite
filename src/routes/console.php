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
| Os blocos seguintes da 246 penduram aqui a entrega ao Sócrates (outbox,
| bloco 2) e o expurgo LGPD dos contatos.
*/

Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
