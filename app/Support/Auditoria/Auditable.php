<?php

declare(strict_types=1);

namespace App\Support\Auditoria;

use App\Models\Auditoria;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait Auditable
{
    private const CAMPOS_SENSIVEIS = [
        'password',
        'remember_token',
        'dois_fatores_segredo',
        'dois_fatores_codigos_recuperacao',
    ];

    private const CAMPOS_IGNORADOS = [
        'created_at',
        'updated_at',
        'ultimo_acesso_em',
    ];

    public static function bootAuditable(): void
    {
        static::created(fn (Model $m) => $m->registrarAuditoria('criado'));
        static::updated(fn (Model $m) => $m->registrarAuditoria('alterado'));
        static::deleted(fn (Model $m) => $m->registrarAuditoria('removido'));

        if (method_exists(static::class, 'restored')) {
            static::restored(fn (Model $m) => $m->registrarAuditoria('restaurado'));
        }
    }

    public function auditorias(): MorphMany
    {
        return $this->morphMany(Auditoria::class, 'auditavel');
    }

    protected function registrarAuditoria(string $evento): void
    {
        $usuario = auth()->user();

        $comum = [
            'evento' => $evento,

            'user_id' => $usuario?->exists ? $usuario->getKey() : null,

            'user_nome' => $usuario?->name,
            'ip' => request()->ip(),
            'user_agent' => mb_substr((string) request()->userAgent(), 0, 255) ?: null,
            'ocorreu_em' => now(),
        ];

        if ($evento !== 'alterado') {
            Auditoria::create([...$comum, 'auditavel_type' => static::class, 'auditavel_id' => $this->getKey()]);

            return;
        }

        foreach ($this->mudancasAuditaveis() as $campo => [$de, $para]) {
            Auditoria::create([
                ...$comum,
                'auditavel_type' => static::class,
                'auditavel_id' => $this->getKey(),
                'campo' => $campo,
                'de' => $de,
                'para' => $para,
            ]);
        }
    }

    protected function mudancasAuditaveis(): array
    {
        $mudancas = [];

        foreach ($this->getChanges() as $campo => $novo) {
            if (in_array($campo, self::CAMPOS_IGNORADOS, strict: true)) {
                continue;
            }

            if (in_array($campo, self::CAMPOS_SENSIVEIS, strict: true)) {
                $mudancas[$campo] = [null, null];

                continue;
            }

            $mudancas[$campo] = [
                $this->paraTexto($this->getOriginal($campo)),
                $this->paraTexto($novo),
            ];
        }

        return $mudancas;
    }

    private function paraTexto(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        if (is_bool($valor)) {
            return $valor ? 'sim' : 'não';
        }

        if ($valor instanceof \DateTimeInterface) {
            return $valor->format('Y-m-d H:i:s');
        }

        if (is_array($valor) || is_object($valor)) {
            return json_encode($valor, JSON_UNESCAPED_UNICODE);
        }

        return mb_substr((string) $valor, 0, 1000);
    }
}
