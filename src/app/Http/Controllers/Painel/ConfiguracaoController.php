<?php

declare(strict_types=1);

namespace App\Http\Controllers\Painel;

use App\Http\Controllers\Controller;
use App\Support\ConfiguracaoDoSite;
use App\Support\ImagemDoSite;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;

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

    /** Sobe (ou tira, com `remover`) a imagem de uma chave do tipo `imagem`. */
    public function imagem(Request $request, string $chave, ImagemDoSite $imagens): RedirectResponse
    {
        abort_unless((ConfiguracaoDoSite::CATALOGO[$chave]['tipo'] ?? null) === 'imagem', 404);

        if ($request->boolean('remover')) {
            ConfiguracaoDoSite::gravarImagem($chave, null);

            return back()->with('sucesso', 'Imagem removida. O site voltou para a imagem de sempre.');
        }

        [$largura, $altura] = ConfiguracaoDoSite::OG;
        $request->validate([
            'arquivo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:8192', "dimensions:min_width={$largura},min_height={$altura},max_width=8000,max_height=8000"],
        ], [
            'arquivo.mimes' => 'Envie uma imagem em JPG, PNG ou WebP.',
            'arquivo.max' => 'A imagem passa de 8 MB.',
            'arquivo.dimensions' => "A imagem precisa ter pelo menos {$largura}×{$altura}.",
        ]);

        try {
            $caminho = $imagens->recortar($request->file('arquivo'), $largura, $altura);
        } catch (RuntimeException $e) {
            throw ValidationException::withMessages(['arquivo' => $e->getMessage()]);
        }

        ConfiguracaoDoSite::gravarImagem($chave, $caminho);

        return back()->with('sucesso', 'Imagem trocada. Redes que já guardaram a anterior podem levar alguns dias para atualizar.');
    }
}
