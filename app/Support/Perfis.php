<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Str;

final class Perfis
{
    public const ADMINISTRADOR = 'administrador';

    public const BANDA = 'banda';

    public const PRODUCAO = 'producao';

    private const ROTULOS = [
        self::ADMINISTRADOR => 'Administrador',
        self::BANDA => 'Integrante da banda',
        self::PRODUCAO => 'Produção',
    ];

    private const DESCRICOES = [
        self::ADMINISTRADOR => 'Tudo, inclusive contas de acesso, configuração do site e trilha de auditoria.',
        self::BANDA => 'O conteúdo do site: agenda, vídeos, fotos, repertório, dúvidas e materiais. Vê os pedidos de contratação, mas não os valores.',
        self::PRODUCAO => 'Agenda, locais e o funil de contratação de ponta a ponta, valores inclusive. Não mexe em conta de acesso.',
    ];

    public static function rotulo(?string $nome): ?string
    {
        if ($nome === null || $nome === '') {
            return null;
        }

        return self::ROTULOS[$nome] ?? Str::headline($nome);
    }

    public static function descricao(?string $nome): ?string
    {
        return $nome === null ? null : (self::DESCRICOES[$nome] ?? null);
    }
}
