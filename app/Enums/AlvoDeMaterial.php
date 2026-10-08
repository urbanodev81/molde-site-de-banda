<?php

declare(strict_types=1);

namespace App\Enums;

use App\Models\Contratacao;
use App\Models\Integrante;
use App\Models\Local;
use App\Models\Show;
use Illuminate\Database\Eloquent\Model;

enum AlvoDeMaterial: string
{
    case Show = 'show';
    case Local = 'local';
    case Integrante = 'integrante';
    case Contratacao = 'contratacao';

    public function modelo(): string
    {
        return match ($this) {
            self::Show => Show::class,
            self::Local => Local::class,
            self::Integrante => Integrante::class,
            self::Contratacao => Contratacao::class,
        };
    }

    public function rotulo(): string
    {
        return match ($this) {
            self::Show => 'Show',
            self::Local => 'Local',
            self::Integrante => 'Integrante',
            self::Contratacao => 'Pedido',
        };
    }

    public function permissao(): string
    {
        return match ($this) {
            self::Show => 'shows.ver',
            self::Local => 'locais.ver',
            self::Integrante => 'integrantes.ver',
            self::Contratacao => 'contratacoes.ver',
        };
    }

    public function reservado(): bool
    {
        return $this === self::Contratacao;
    }

    public static function de(Model $alvo): self
    {
        foreach (self::cases() as $caso) {
            if ($alvo instanceof ($caso->modelo())) {
                return $caso;
            }
        }

        throw new \InvalidArgumentException($alvo::class.' não recebe material.');
    }
}
