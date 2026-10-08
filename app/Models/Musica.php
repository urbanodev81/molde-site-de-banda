<?php

declare(strict_types=1);

namespace App\Models;

use App\Support\Arquivos;
use App\Support\VideoExterno;
use App\Support\Youtube;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Musica extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'musicas';

    protected $guarded = ['id'];

    protected $attributes = ['publicada' => true, 'destaque' => false];

    protected function casts(): array
    {
        return [
            'publicada' => 'boolean',
            'destaque' => 'boolean',
        ];
    }

    public function shows(): BelongsToMany
    {
        return $this->belongsToMany(Show::class, 'musica_show')
            ->withPivot(['ordem', 'bloco'])
            ->withTimestamps();
    }

    public function scopePublicadas(Builder $query): Builder
    {
        return $query->where('publicada', true)->orderBy('ordem')->orderBy('titulo');
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function temVideo(): bool
    {
        return $this->versao() !== null;
    }

    public function versao(): ?array
    {
        $video = $this->relationLoaded('video') ? $this->getRelation('video') : $this->video;

        if ($video && $video->publicado && $video->reproduzivel()) {
            $externo = $video->externo();

            return [
                'titulo' => $this->linha(),
                'youtube' => (string) $video->youtube_id,
                'mp4' => (string) Arquivos::url($video->arquivo_mp4_path),
                'webm' => (string) Arquivos::url($video->arquivo_webm_path),
                'embed' => $externo['embed'] ?? '',
                'origem' => $externo['url'] ?? '',
                'provedor' => $externo ? VideoExterno::rotulo($externo['provedor']) : '',
                'capa' => (string) (Arquivos::url($video->capa_path) ?: Youtube::capa($video->youtube_id)),
                'onde' => (string) $video->ondeFoi(),
            ];
        }

        if (filled($this->youtube_id)) {
            return [
                'titulo' => $this->linha(),
                'youtube' => $this->youtube_id,
                'mp4' => '', 'webm' => '', 'embed' => '', 'origem' => '', 'provedor' => '',
                'capa' => (string) Youtube::capa($this->youtube_id),
                'onde' => '',
            ];
        }

        return null;
    }

    public function linha(): string
    {
        return $this->artista ? "{$this->titulo} — {$this->artista}" : $this->titulo;
    }
}
