<?php

declare(strict_types=1);

namespace App\Support\Contatos;

use App\Jobs\Socrates\EntregarOutboxDoSocratesJob;
use App\Models\Contato;
use App\Services\Socrates\OutboxDoSocrates;
use Illuminate\Support\Facades\Log;
use Throwable;
use UrbanoDev\Blog\Blog;

/**
 * Onde o lead do site é guardado ANTES do e-mail (04/11/2026, bloco 2 da 246).
 *
 * Até aqui o lead era só o e-mail, e se o SMTP falhasse ele vivia numa linha
 * do `laravel.log`. Agora a ordem é: grava → avisa por e-mail → conta ao
 * Sócrates. Cada passo falha sozinho:
 *
 *  - **Banco fora:** `guardar()` devolve null e o controller segue o caminho de
 *    antes (e-mail, e log se o e-mail também falhar). O site público não passa
 *    a depender do banco por causa do formulário.
 *  - **E-mail fora:** o lead já está guardado; o painel mostra "o aviso não
 *    saiu" e o visitante vê sucesso, porque para ele deu certo.
 *  - **Sócrates fora:** a outbox reentrega.
 */
class CaixaDeContatos
{
    public function __construct(private readonly OutboxDoSocrates $outbox) {}

    /**
     * @param  array{origem: string, nome: string, email: string, telefone: string, mensagem?: ?string, demo?: ?string, texto?: ?string}  $dados
     */
    public function guardar(array $dados): ?Contato
    {
        try {
            return Contato::create([
                'origem' => $dados['origem'],
                'demo' => self::demoValida($dados['demo'] ?? null),
                'texto' => self::textoValido($dados['texto'] ?? null),
                'nome' => $dados['nome'],
                'email' => $dados['email'],
                'telefone' => $dados['telefone'],
                'mensagem' => $dados['mensagem'] ?? null,
                // Só chega aqui quem marcou o aceite: o `consent` é `accepted` na validação.
                'consentimento_em' => now(),
            ]);
        } catch (Throwable $e) {
            // Sem o dado pessoal: o controller ainda vai tentar o e-mail, e o
            // log do recado inteiro só acontece se os DOIS falharem.
            Log::error('Contato não foi gravado no banco — seguindo só com o e-mail', ['erro' => $e->getMessage()]);

            return null;
        }
    }

    public function emailEnviado(?Contato $contato): void
    {
        $this->marcar($contato, ['email_enviado_em' => now(), 'email_erro' => null]);
    }

    public function emailFalhou(?Contato $contato, Throwable $e): void
    {
        $this->marcar($contato, ['email_erro' => mb_substr($e->getMessage(), 0, 500)]);
    }

    /**
     * Conta ao Sócrates (`lead.recebido`). Desligado por `SOCRATES_LEADS` até o
     * endpoint de lá existir; a outbox decide e não grava nada se estiver.
     */
    public function contarAoSocrates(?Contato $contato): void
    {
        if ($contato === null) {
            return;
        }

        try {
            $linha = $this->outbox->registrar(OutboxDoSocrates::LEAD_RECEBIDO, [
                'id' => $contato->uuid,
                'origem' => $contato->origem,
                'origem_legivel' => $contato->origemLegivel(),
                'demo' => $contato->demo,
                'texto' => $contato->texto,
                'nome' => $contato->nome,
                'email' => $contato->email,
                'telefone' => $contato->telefone,
                'mensagem' => $contato->mensagem,
                'recebido_em' => $contato->created_at->toIso8601String(),
                'painel_url' => route('painel.contatos.show', $contato),
            ], $contato->created_at);
        } catch (Throwable $e) {
            Log::warning('Lead não entrou na outbox do Sócrates', ['contato' => $contato->uuid, 'erro' => $e->getMessage()]);

            return;
        }

        if ($linha !== null) {
            EntregarOutboxDoSocratesJob::dispatchAfterResponse();
        }
    }

    /** Só o slug de uma demonstração que existe. O endereço escolhe; nunca escreve. */
    public static function demoValida(mixed $slug): ?string
    {
        return is_string($slug) && preg_match('/^[a-z0-9-]{1,40}$/', $slug) && is_array(config('demonstracoes.'.$slug))
            ? $slug
            : null;
    }

    /** Só o slug de um texto do blog que existe. */
    public static function textoValido(mixed $slug): ?string
    {
        return is_string($slug) && preg_match('/^[a-z0-9-]{1,120}$/', $slug) && app(Blog::class)->achar($slug) !== null
            ? $slug
            : null;
    }

    /** @param  array<string, mixed>  $valores */
    private function marcar(?Contato $contato, array $valores): void
    {
        if ($contato === null) {
            return;
        }

        try {
            // Sem disparar a trilha: é o sistema anotando o aviso, não alguém mexendo no lead.
            Contato::withoutEvents(fn () => $contato->forceFill($valores)->save());
        } catch (Throwable) {
            // O lead já está guardado; perder a marca do e-mail não é motivo para 500.
        }
    }
}
