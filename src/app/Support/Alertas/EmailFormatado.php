<?php

namespace App\Support\Alertas;

use Illuminate\Mail\Message;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;

/**
 * E-mail no layout padrão do sistema — o mesmo das notificações (cabeçalho com
 * a marca, corpo em cartão, botão, rodapé) — para quem não é uma Notification.
 *
 * Existe porque, até 29/09/2026, alertas ao operador, erro 500 e até o "defina
 * sua senha" do comando remoto saíam por `Mail::raw()`: texto corrido, sem
 * marca, e com o nome do INQUILINO no remetente quando disparados dentro dele
 * ("De: Imóveis Jacob" num alerta de segurança nosso).
 *
 * Arquivo idêntico nos repositórios do ecossistema (PADROES.md § e-mail). Se
 * mudar aqui, mude nos outros.
 *
 *     EmailFormatado::para($destino)
 *         ->assunto('[USI] Suporte entrou na conta')
 *         ->titulo('Alerta de segurança')
 *         ->paragrafo('Uma sessão de suporte foi aberta.')
 *         ->campo('IP', $ip)
 *         ->botao('Abrir', $url)
 *         ->remetente('USI')      // nome no "De:"; o endereço é o de sempre
 *         ->enviar();
 *
 * Todo texto variável é escapado: o corpo passa por Markdown, e um motivo ou
 * uma mensagem de exceção com `*` ou `[` não pode virar formatação ou link.
 */
final class EmailFormatado
{
    private string $assunto = '';

    private ?string $titulo = null;

    /** @var list<array{0:string,1:string,2?:string}> */
    private array $blocos = [];

    private ?string $remetente = null;

    private ?array $responderPara = null;

    private ?string $assinatura = null;

    private function __construct(private readonly string $para) {}

    public static function para(string $para): self
    {
        return new self($para);
    }

    public function assunto(string $assunto): self
    {
        $this->assunto = $assunto;

        return $this;
    }

    public function titulo(string $titulo): self
    {
        $this->titulo = $titulo;

        return $this;
    }

    public function paragrafo(string $texto): self
    {
        $this->blocos[] = ['p', $texto];

        return $this;
    }

    public function campo(string $rotulo, mixed $valor): self
    {
        $this->blocos[] = ['c', $rotulo, self::texto($valor)];

        return $this;
    }

    /** @param  array<string, mixed>  $campos */
    public function campos(array $campos): self
    {
        foreach ($campos as $rotulo => $valor) {
            $this->campo((string) $rotulo, $valor);
        }

        return $this;
    }

    /**
     * Para corpo que já vem montado como texto: "Rótulo: valor" (rótulo curto,
     * sem espaço antes dos dois-pontos) vira campo; o resto vira parágrafo.
     */
    public function textoCorrido(string $texto): self
    {
        foreach (preg_split('/\R/', trim($texto)) ?: [] as $linha) {
            $linha = trim($linha);
            if ($linha === '') {
                continue;
            }

            preg_match('/^([^:]{1,30}):\s+(.+)$/u', $linha, $m) === 1
                ? $this->campo($m[1], $m[2])
                : $this->paragrafo($linha);
        }

        return $this;
    }

    public function botao(string $rotulo, string $url): self
    {
        $this->blocos[] = ['b', $rotulo, $url];

        return $this;
    }

    public function remetente(string $nome): self
    {
        $this->remetente = $nome;

        return $this;
    }

    public function responderPara(string $email, ?string $nome = null): self
    {
        $this->responderPara = [$email, $nome];

        return $this;
    }

    public function assinatura(string $assinatura): self
    {
        $this->assinatura = $assinatura;

        return $this;
    }

    public function enviar(): void
    {
        $html = (string) $this->mensagem()->render();
        $texto = $this->textoPuro();

        Mail::send([], [], function (Message $m) use ($html, $texto) {
            $m->to($this->para)->subject($this->assunto)->html($html)->text($texto);

            if ($this->remetente !== null) {
                $m->from((string) config('mail.from.address'), $this->remetente);
            }
            if ($this->responderPara !== null) {
                $m->replyTo($this->responderPara[0], $this->responderPara[1]);
            }
        });
    }

    public function mensagem(): MailMessage
    {
        $msg = (new MailMessage)->subject($this->assunto);

        if ($this->titulo !== null) {
            $msg->greeting(self::escapar($this->titulo));
        }

        foreach ($this->blocos as $bloco) {
            match ($bloco[0]) {
                'p' => $msg->line(self::escapar($bloco[1])),
                'c' => $msg->line('**'.self::escapar($bloco[1]).':** '.self::escapar($bloco[2])),
                'b' => $msg->action($bloco[1], $bloco[2]),
            };
        }

        return $msg->salutation(self::escapar($this->assinatura ?? (string) config('app.name')));
    }

    private function textoPuro(): string
    {
        $linhas = $this->titulo !== null ? [$this->titulo, ''] : [];

        foreach ($this->blocos as $bloco) {
            $linhas[] = match ($bloco[0]) {
                'p' => $bloco[1]."\n",
                'c' => $bloco[1].': '.$bloco[2],
                'b' => "\n".$bloco[1].': '.$bloco[2]."\n",
            };
        }

        $linhas[] = '';
        $linhas[] = $this->assinatura ?? (string) config('app.name');

        return implode("\n", $linhas);
    }

    private static function texto(mixed $valor): string
    {
        if ($valor === null || $valor === '') {
            return '—';
        }

        return is_scalar($valor)
            ? (string) $valor
            : (string) json_encode($valor, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /** Barra invertida antes do que o Markdown leria como formatação. */
    private static function escapar(string $texto): string
    {
        return preg_replace('/([\\\\`*_{}\[\]()#+\-.!|~>])/', '\\\\$1', $texto) ?? $texto;
    }
}
