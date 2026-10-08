<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Contratacao;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NovoPedidoDeContratacao extends Mailable
{
    use Queueable;
    use SerializesModels;

    public function __construct(public readonly Contratacao $pedido) {}

    public function envelope(): Envelope
    {
        $quando = $this->pedido->data_pretendida?->format('d/m/Y') ?? 'data a combinar';

        return new Envelope(
            subject: "Pedido de show · {$this->pedido->tipo_evento->rotulo()} · {$quando}",

            replyTo: $this->pedido->email ? [$this->pedido->email] : [],
        );
    }

    public function content(): Content
    {
        return new Content(markdown: 'emails.novo-pedido');
    }
}
