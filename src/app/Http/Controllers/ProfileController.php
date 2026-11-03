<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\AparelhoConfiavel;
use App\Services\Auth\AparelhosConfiaveis;
use App\Services\Auth\TotpService;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request, TotpService $totp, AparelhosConfiaveis $aparelhos): Response
    {
        $usuario = $request->user();
        $atual = $aparelhos->atual($usuario, $request);

        // Ativação em curso (segredo gerado, ainda não confirmado): o QR e a
        // chave vão para a tela SÓ nesta janela. Depois de confirmado o
        // segredo nunca mais sai do servidor — o `$hidden` do model segura o
        // `auth.user`, e aqui ninguém o põe de volta.
        $pendente = null;

        if ($usuario->dois_fatores_segredo && ! $usuario->doisFatoresAtivo()) {
            $pendente = [
                'secret' => $usuario->dois_fatores_segredo,
                // SVG direto do bacon (03/11/2026): o `simple-qrcode` do molde
                // exige a extensão GD só para instalar, e aqui só sai SVG.
                'qrSvg' => (new Writer(new ImageRenderer(new RendererStyle(200, 1), new SvgImageBackEnd)))
                    ->writeString($totp->getOtpAuthUrl($usuario->dois_fatores_segredo, $usuario->email, config('marca.nome'))),
            ];
        }

        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $usuario instanceof MustVerifyEmail,
            'status' => session('status'),
            'twoFactorEnabled' => $usuario->doisFatoresAtivo(),
            'twoFactorRequired' => config('auth.dois_fatores_obrigatorio') && $usuario->exigeDoisFatores(),
            'twoFactorPending' => $pendente,
            'twoFactorConfirmedAt' => $usuario->dois_fatores_confirmado_em?->format('d/m/Y H:i'),
            'twoFactorRecoveryCodesLeft' => count($usuario->dois_fatores_codigos_recuperacao ?? []),
            // Texto puro, só na resposta seguinte à confirmação.
            'twoFactorRecoveryCodes' => session('two_factor_recovery_codes'),
            'aparelhosConfiaveis' => $aparelhos->listar($usuario)->map(fn (AparelhoConfiavel $a) => [
                'uuid' => $a->uuid,
                'apelido' => $a->apelido,
                'descricao' => $a->descricao,
                'ultimoUso' => $a->ultimo_uso_em->format('d/m/Y H:i'),
                'expiraEm' => $a->expira_em->format('d/m/Y'),
                'atual' => $a->uuid === $atual,
            ])->all(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }
}
