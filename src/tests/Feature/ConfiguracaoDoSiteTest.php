<?php

declare(strict_types=1);

use App\Models\Auditoria;
use App\Models\Configuracao;
use App\Models\User;
use App\Support\ConfiguracaoDoSite;
use App\Support\Perfis;
use Database\Seeders\PerfisESeguranca;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Configuração do site (bloco 4a da 246, 06/11/2026). O que se prova: o valor
 * gravado aparece em todo lugar que antes tinha o texto copiado (home, rodapé,
 * JSON-LD, llms.txt, privacidade, blog); vazio volta ao texto de sempre; chave
 * fora do catálogo não grava; só o administrador mexe. O "banco fora" fica no
 * `SitePublicoSemBancoTest`, que pede as mesmas páginas sem conexão.
 */
beforeEach(function () {
    $this->seed(PerfisESeguranca::class);
    ConfiguracaoDoSite::esquecer();
});

function contaDoSite(string $perfil): User
{
    $usuario = User::create([
        'name' => 'Conta '.$perfil,
        'email' => 'config-'.$perfil.'@teste.local',
        'password' => 'senha-de-teste-123',
        'ativo' => true,
        'email_verified_at' => now(),
    ]);
    $usuario->syncRoles([$perfil]);

    return $usuario;
}

it('sem nada gravado, o site mostra o texto de sempre', function () {
    $this->get('/')->assertOk()
        ->assertSee('Software que resolve <em>problemas reais</em>.', false)
        ->assertSee('contato@urbisdev.tech')
        ->assertSee('wa.me/5511976862551', false)
        ->assertDontSee('class="s-ig"', false);
    $this->get('/llms.txt')->assertSee('+55 11 97686-2551');
});

it('o administrador grava, e o valor aparece em todo lugar', function () {
    $admin = contaDoSite(Perfis::ADMINISTRADOR);

    $this->actingAs($admin)->get('/painel/site/configuracoes')->assertOk()
        ->assertInertia(fn ($p) => $p->component('Painel/Configuracoes/Editar')->has('grupos.contato'));

    $this->actingAs($admin)->put('/painel/site/configuracoes', ['valores' => [
        'contato.email' => 'ola@exemplo.com',
        'contato.whatsapp' => '5521988887777',
        'redes.instagram' => 'https://instagram.com/urbisdev',
        'empresa.descricao' => 'Descrição nova da casa.',
        'home.hero_titulo' => 'Título <b>novo</b> com *destaque*',
        'home.contato_texto' => '',
    ]])->assertSessionHasNoErrors()->assertSessionHas('sucesso');

    $home = $this->get('/')->assertOk();
    $home->assertSee('mailto:ola@exemplo.com', false)
        ->assertSee('wa.me/5521988887777', false)
        ->assertSee('https://instagram.com/urbisdev', false)
        ->assertSee('+55 21 98888-7777')                       // JSON-LD
        ->assertSee('Descrição nova da casa.')
        // HTML digitado sai escapado; só o *destaque* vira <em>.
        ->assertSee('Título &lt;b&gt;novo&lt;/b&gt; com <em>destaque</em>', false)
        ->assertDontSee('contato@urbisdev.tech')
        // Vazio volta ao texto de sempre.
        ->assertSee('Conte pra gente o que você precisa.');

    $this->get('/llms.txt')->assertSee('ola@exemplo.com')->assertSee('+55 21 98888-7777')->assertSee('Descrição nova da casa.');
    $this->get('/privacidade')->assertSee('ola@exemplo.com');

    expect(Auditoria::where('auditavel_type', Configuracao::class)->count())->toBeGreaterThan(0);
});

it('recusa WhatsApp fora do formato e endereço sem https', function () {
    $this->actingAs(contaDoSite(Perfis::ADMINISTRADOR))->put('/painel/site/configuracoes', ['valores' => [
        'contato.whatsapp' => '(11) 97686-2551',
        'redes.linkedin' => 'javascript:alert(1)',
    ]])->assertSessionHasErrors(['valores.contato.whatsapp' => 'Só números, começando por 55 e o DDD. Ex.: 5511976862551.', 'valores.redes.linkedin' => 'Use o endereço completo, começando por https://.']);

    expect(Configuracao::count())->toBe(0);
});

it('chave fora do catálogo não é gravada', function () {
    $this->actingAs(contaDoSite(Perfis::ADMINISTRADOR))->put('/painel/site/configuracoes', ['valores' => [
        'contato.zap' => '5511999990000',
    ]]);

    expect(Configuracao::where('chave', 'contato.zap')->exists())->toBeFalse();
});

it('a redação não abre nem grava a configuração', function () {
    $redacao = contaDoSite(Perfis::REDACAO);

    $this->actingAs($redacao)->get('/painel/site/configuracoes')->assertForbidden();
    $this->actingAs($redacao)->put('/painel/site/configuracoes', ['valores' => ['contato.email' => 'x@y.com']])->assertForbidden();
});

it('a imagem de compartilhamento é recortada em 1200x630 e troca a do site', function () {
    Storage::fake('public');
    $admin = contaDoSite(Perfis::ADMINISTRADOR);

    $this->actingAs($admin)->post('/painel/site/configuracoes/imagem/empresa.imagem_og', [
        'arquivo' => UploadedFile::fake()->image('og.png', 1600, 1200),
    ])->assertSessionHasNoErrors();

    $caminho = ConfiguracaoDoSite::valor('empresa.imagem_og');
    expect($caminho)->toStartWith('site/')->toEndWith('.jpg');
    Storage::disk('public')->assertExists($caminho);
    [$largura, $altura] = getimagesizefromstring(Storage::disk('public')->get($caminho));
    expect([$largura, $altura])->toBe([1200, 630]);
    $this->get('/')->assertSee(Storage::disk('public')->url($caminho), false);

    // Remover volta ao arquivo de sempre e apaga o enviado.
    $this->actingAs($admin)->post('/painel/site/configuracoes/imagem/empresa.imagem_og', ['remover' => true]);
    Storage::disk('public')->assertMissing($caminho);
    $this->get('/')->assertSee('og-urbisdev.png', false);
});

it('imagem pequena demais ou chave que não é imagem são recusadas', function () {
    Storage::fake('public');
    $admin = contaDoSite(Perfis::ADMINISTRADOR);

    $this->actingAs($admin)->post('/painel/site/configuracoes/imagem/empresa.imagem_og', [
        'arquivo' => UploadedFile::fake()->image('og.png', 600, 300),
    ])->assertSessionHasErrors('arquivo');
    $this->actingAs($admin)->post('/painel/site/configuracoes/imagem/contato.email', [
        'arquivo' => UploadedFile::fake()->image('og.png', 1600, 1200),
    ])->assertNotFound();
});
