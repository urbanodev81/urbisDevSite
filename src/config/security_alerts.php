<?php

/*
| Destino dos avisos ao OPERADOR (nós): erro 500.
|
| Com padrão, e não só env: alerta que depende de uma variável esquecida não é
| alerta (o USI rodou em prod sem ALERT_EMAIL — pendência 131). O site
| institucional não tem login: o único aviso é o de erro 500, por e-mail
| (29/09/2026, pendência 134).
*/
return [
    'alert_email' => env('SECURITY_ALERT_EMAIL') ?: 'ricardo@urbanodev.com.br',
    'service' => env('SECURITY_ALERT_SERVICE', 'urbisdev-site'),
];
