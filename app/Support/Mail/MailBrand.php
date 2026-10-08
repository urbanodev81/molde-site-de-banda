<?php

declare(strict_types=1);

namespace App\Support\Mail;

class MailBrand
{
    public static function resolve(): array
    {
        $nome = config('app.name', 'A melhor banda');

        return [
            'name' => $nome,
            'url' => config('app.url'),

            'primary' => '#611EFF',
            'on_primary' => '#ffffff',
            'on_primary_muted' => '#EEE7FF',

            'logo' => null,
            'initials' => 'G',

            'tagline' => 'Banda de rock · São Paulo',
            'support_email' => config('mail.from.address'),
            'footer_note' => null,

            'vendor' => [
                'name' => 'Exemplo',
                'legal_name' => 'EXEMPLO LTDA',
                'cnpj' => '00.000.000/0001-00',
                'url' => 'https://exemplo.test',
                'email' => 'alex@exemplo.test',
            ],

            'is_tenant' => false,
            'product' => null,
        ];
    }
}
