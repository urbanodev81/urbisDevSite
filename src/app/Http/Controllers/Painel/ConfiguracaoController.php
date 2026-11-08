<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Support\ConfiguracaoDoSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use UrbanoDev\Midia\Models\Midia;
use Inertia\Inertia;
use Inertia\Response;

/**
 * A configuração do site (bloco 4a da 246, 06/11/2026; veio do Duo).
 *
 * A tela inteira se desenha do catálogo (`ConfiguracaoDoSite::paraTela()`) e a
 * validação sai do mesmo array (`::regras()`): chave nova aparece na tela e
 * ganha validação sem ninguém tocar neste arquivo.
 */
class ConfiguracaoController extends Controller
{
    public function edit(): Response
    {
        return Inertia::render('Painel/Configuracoes/Editar', [
            'grupos' => ConfiguracaoDoSite::paraTela(),
            'rotulos' => ConfiguracaoDoSite::rotulosDosGrupos(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $dados = $request->validate(ConfiguracaoDoSite::regras(), [
            // A chave do erro é a do campo SEM o escape do ponto.
            'valores.contato.whatsapp.regex' => 'Só números, começando por 55 e o DDD. Ex.: 5511976862551.',
            'valores.contato.email.email' => 'Esse e-mail não parece válido.',
            'valores.redes.instagram.url' => 'Use o endereço completo, começando por https://.',
            'valores.redes.linkedin.url' => 'Use o endereço completo, começando por https://.',
            'valores.*.max' => 'Passou do limite de :max letras.',
        ]);

        ConfiguracaoDoSite::gravar($dados['valores'] ?? []);

        return back()->with('sucesso', 'Configuração salva. O site já está mostrando o novo valor.');
    }

    /**
     * Troca (ou tira, com `remover`) a imagem de uma chave do tipo `imagem`.
     * A imagem já subiu pelo pacote (perfil `capa`, 1200×630 JPEG): aqui chega
     * só o `uuid` escolhido na galeria (255, 07/11/2026).
     */
    public function imagem(Request $request, string $chave): RedirectResponse
    {
        abort_unless((ConfiguracaoDoSite::CATALOGO[$chave]['tipo'] ?? null) === 'imagem', 404);

        if ($request->boolean('remover')) {
            ConfiguracaoDoSite::gravarImagem($chave, null);

            return back()->with('sucesso', 'Imagem removida. O site voltou para a imagem de sempre.');
        }

        $dados = $request->validate(['uuid' => ['required', 'uuid']], ['uuid.required' => 'Escolha uma imagem.']);
        $midia = Midia::query()->daGaleria()->where('uuid', $dados['uuid'])->where('perfil', 'capa')->first();
        if ($midia === null) {
            throw ValidationException::withMessages(['uuid' => 'Essa imagem não serve aqui: escolha ou suba uma no tamanho de compartilhamento.']);
        }

        ConfiguracaoDoSite::gravarImagem($chave, $midia->caminho);

        return back()->with('sucesso', 'Imagem trocada. Redes que já guardaram a anterior podem levar alguns dias para atualizar.');
    }
}
