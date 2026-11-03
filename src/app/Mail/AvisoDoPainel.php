<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Um aviso do painel a uma conta: texto simples e, se houver, um botão.
 *
 * Nasceu em 10/10/2026 para contar a alguém que a verificação em duas etapas
 * da conta dela foi desligada por outra pessoa. O Mailable só desenha: quem
 * chama diz o assunto e o corpo.
 */
class AvisoDoPainel extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(
        public readonly string $assunto,
        public readonly string $corpo,
        public readonly ?string $url = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->assunto);
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.aviso');
    }
}
