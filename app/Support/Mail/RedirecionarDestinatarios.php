<?php

declare(strict_types=1);

namespace App\Support\Mail;

use Illuminate\Mail\Events\MessageSending;
use RuntimeException;
use Symfony\Component\Mime\Address;

class RedirecionarDestinatarios
{
    public const CABECALHO = 'X-Destinatario-Original';

    public function handle(MessageSending $evento): void
    {
        $configurado = (array) config('mail.redirecionar_para', []);

        if ($configurado === []) {
            return;
        }

        $destinos = array_values(array_filter(
            $configurado,
            fn (mixed $e): bool => is_string($e)
                && filter_var($e, FILTER_VALIDATE_EMAIL) !== false,
        ));

        if ($destinos === []) {
            throw new RuntimeException(
                'MAIL_REDIRECIONAR_PARA está preenchido, mas nenhum endereço é válido: '
                .implode(', ', array_map(strval(...), $configurado))
                .'. Nenhum e-mail sai enquanto isso não for corrigido.'
            );
        }

        $email = $evento->message;
        $cabecalhos = $email->getHeaders();

        $originais = array_unique(array_map(
            fn (Address $a): string => $a->getAddress(),
            [...$email->getTo(), ...$email->getCc(), ...$email->getBcc()],
        ));

        $email->to(...array_map(
            fn (string $e): Address => new Address($e),
            $destinos,
        ));

        $cabecalhos->remove('Cc');
        $cabecalhos->remove('Bcc');

        if ($originais === []) {
            return;
        }

        $lista = implode(', ', $originais);

        $cabecalhos->remove(self::CABECALHO);
        $cabecalhos->addTextHeader(self::CABECALHO, $lista);

        $email->subject('[→ '.$lista.'] '.((string) $email->getSubject()));
    }
}
