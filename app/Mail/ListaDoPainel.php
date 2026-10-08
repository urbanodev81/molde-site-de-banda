<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

class ListaDoPainel extends Mailable
{
    use Queueable;

    public function __construct(
        public readonly array $nome,
        public readonly array $itens,
        public readonly User $remetente,
        public readonly ?string $recado = null,
    ) {}

    public function envelope(): Envelope
    {
        $quantos = count($this->itens) === 1 ? "1 {$this->nome[0]}" : count($this->itens)." {$this->nome[1]}";

        return new Envelope(
            subject: config('app.name')." · {$quantos}",

            replyTo: [$this->remetente->email],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.lista-do-painel');
    }
}
