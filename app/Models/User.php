<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Auditoria\Auditable;
use App\Support\Perfis;
use App\Support\TemUuid;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use Auditable;
    use HasFactory;
    use HasPushSubscriptions;
    use HasRoles;
    use Notifiable;
    use SoftDeletes;
    use TemUuid;

    protected $guarded = ['id'];

    protected $attributes = ['ativo' => true];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'ultimo_acesso_em' => 'datetime',
            'password' => 'hashed',
            'ativo' => 'boolean',
        ];
    }

    public function contratacoes(): HasMany
    {
        return $this->hasMany(Contratacao::class, 'responsavel_id');
    }

    public function administrador(): bool
    {
        return $this->hasRole(Perfis::ADMINISTRADOR);
    }

    public function perfilPrincipal(): ?string
    {
        return Perfis::rotulo($this->roles->first()?->name);
    }

    public function registrarAcesso(): void
    {
        $this->forceFill(['ultimo_acesso_em' => now()])->saveQuietly();
    }

    public function scopeAtivos(Builder $query): Builder
    {
        return $query->where('ativo', true);
    }
}
