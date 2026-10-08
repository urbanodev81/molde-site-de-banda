<?php

declare(strict_types=1);

namespace App\Enums;

enum StatusContratacao: string
{
    case Novo = 'novo';
    case EmContato = 'em_contato';
    case PropostaEnviada = 'proposta_enviada';
    case Fechado = 'fechado';
    case Perdido = 'perdido';

    public function rotulo(): string
    {
        return match ($this) {
            self::Novo => 'Novo',
            self::EmContato => 'Em contato',
            self::PropostaEnviada => 'Proposta enviada',
            self::Fechado => 'Fechado',
            self::Perdido => 'Perdido',
        };
    }

    public function token(): string
    {
        return match ($this) {
            self::Novo => 'accent',
            self::EmContato => 'info',
            self::PropostaEnviada => 'warning',
            self::Fechado => 'success',
            self::Perdido => 'danger',
        };
    }

    public function icone(): string
    {
        return match ($this) {
            self::Novo => 'inbox',
            self::EmContato => 'message-circle',
            self::PropostaEnviada => 'file-text',
            self::Fechado => 'circle-check',
            self::Perdido => 'circle-x',
        };
    }

    public function encerrado(): bool
    {
        return $this === self::Fechado || $this === self::Perdido;
    }

    public static function opcoes(): array
    {
        return array_map(
            fn (self $c) => ['valor' => $c->value, 'rotulo' => $c->rotulo()],
            self::cases(),
        );
    }
}
