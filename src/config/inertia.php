<?php

/*
|--------------------------------------------------------------------------
| Inertia
|--------------------------------------------------------------------------
|
| Veio do molde do Duo (03/11/2026). Este arquivo existe por UM motivo, e é o único que ele resolve: o caminho
| das páginas.
|
| O Inertia 3 mudou o diretório padrão de `resources/js/Pages` para
| `resources/js/pages` — minúsculo. O nosso é maiúsculo, porque é o que o
| `laravel/breeze` escreve, e em Linux o sistema de arquivos diferencia as
| duas coisas. Sem esta linha, `assertInertia` falha com "Inertia page
| component file [X] does not exist" em toda tela do painel que um teste
| visita — com o arquivo no lugar o tempo todo. O Synapse perdeu seis testes
| assim na subida para o Laravel 13, em 15/09/2026, e a receita do
| `socrates/docs/PADROES.md` §"Subida para o Laravel 13" registra o sintoma.
|
| ⚠️ Aqui o estrago seria maior que nos irmãos: só o PAINEL é Inertia. O site
| público é Blade e não passa por este caminho, então uma quebra de resolução
| de página deixaria a suíte vermelha no painel e o site verde — o que parece
| defeito de teste e não de configuração.
|
| ⚠️ Se um dia as páginas forem renomeadas para minúsculo, é este arquivo que
| some — não o contrário.
|
*/

return [

    'pages' => [

        // Falso em execução normal (custa uma busca em disco por render) e
        // verdadeiro só em teste, logo abaixo — que é onde o erro de nome de
        // componente precisa aparecer.
        'ensure_pages_exist' => false,

        'paths' => [
            resource_path('js/Pages'),
        ],

        'extensions' => [
            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',
        ],

    ],

    'testing' => [

        'ensure_pages_exist' => true,

    ],

];
