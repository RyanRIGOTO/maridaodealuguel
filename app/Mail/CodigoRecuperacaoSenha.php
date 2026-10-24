<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CodigoRecuperacaoSenha extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nome,
        public string $codigo,
        public int $validadeMinutos,
    ) {}

    public function envelope(): Envelope
    {
        $remetente = config('mail.mailers.gmail.username') ?: config('mail.from.address');

        return new Envelope(
            from: new Address($remetente, 'Maridão de Aluguel'),
            subject: 'Seu código para redefinir a senha',
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.codigo-recuperacao');
    }
}
