<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ListaDeFotos extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly array $fotos,
        public readonly User $remetente,
        public readonly ?string $recado = null,
    ) {}

    public function envelope(): Envelope
    {
        $quantas = count($this->fotos) === 1 ? '1 foto' : count($this->fotos).' fotos';

        return new Envelope(
            subject: "A melhor banda · {$quantas}",

            replyTo: [$this->remetente->email],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.lista-de-fotos');
    }
}
