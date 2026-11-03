<?php

declare(strict_types=1);

use App\Support\Captcha\Altcha;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/*
| O captcha da suíte: `Altcha` falso que aceita só `captcha-de-teste`. O
| verdadeiro exige resolver a prova de trabalho; o que se testa aqui é que o
| login PEDE o captcha e recusa o inválido, não a matemática do Altcha.
*/
const CAPTCHA_DE_TESTE = 'captcha-de-teste';

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach(function () {
        // A suíte não depende do build do front (`npm run build`).
        $this->withoutVite();

        $this->mock(Altcha::class, function ($mock) {
            $mock->shouldReceive('verificar')->andReturnUsing(fn (string $t) => $t === CAPTCHA_DE_TESTE);
            $mock->shouldReceive('criarDesafio')->andReturn(['challenge' => 'x']);
        });
    })
    ->in('Feature');
