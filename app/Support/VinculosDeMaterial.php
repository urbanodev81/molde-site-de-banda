<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\AlvoDeMaterial;
use App\Models\Contratacao;
use App\Models\Integrante;
use App\Models\Local;
use App\Models\Material;
use App\Models\MaterialVinculo;
use App\Models\Show;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

final class VinculosDeMaterial
{
    public static function visiveis(?User $usuario): array
    {
        return array_values(array_filter(
            AlvoDeMaterial::cases(),
            fn (AlvoDeMaterial $a) => (bool) $usuario?->can($a->permissao()),
        ));
    }

    public static function soOsQuePodeVer(Builder $materiais, ?User $usuario): Builder
    {
        $ocultos = array_map(
            fn (AlvoDeMaterial $a) => $a->value,
            array_filter(
                AlvoDeMaterial::cases(),
                fn (AlvoDeMaterial $a) => $a->reservado() && ! $usuario?->can($a->permissao()),
            ),
        );

        return $ocultos === []
            ? $materiais
            : $materiais->whereDoesntHave('vinculos', fn (Builder $q) => $q->whereIn('alvo_tipo', $ocultos));
    }

    public static function rotulo(Model $alvo): string
    {
        return match (true) {
            $alvo instanceof Show => $alvo->nome().' · '.$alvo->comeca_em?->format('d/m/Y'),
            $alvo instanceof Local => (string) $alvo->nome,
            $alvo instanceof Integrante => $alvo->comoAparece(),
            $alvo instanceof Contratacao => $alvo->nome.' · '.$alvo->created_at?->format('d/m/Y'),
            default => '',
        };
    }

    public static function opcoes(?User $usuario): array
    {
        $opcoes = [];

        foreach (self::visiveis($usuario) as $alvo) {
            $itens = match ($alvo) {
                AlvoDeMaterial::Show => Show::query()->with('local')->orderByDesc('comeca_em')->limit(100)->get(),
                AlvoDeMaterial::Local => Local::query()->orderBy('nome')->get(),
                AlvoDeMaterial::Integrante => Integrante::query()->orderBy('ordem')->get(),
                AlvoDeMaterial::Contratacao => Contratacao::query()->latest()->limit(100)->get(),
            };

            foreach ($itens as $item) {
                $opcoes[] = [
                    'valor' => $alvo->value.':'.$item->getKey(),
                    'rotulo' => $alvo->rotulo().' · '.self::rotulo($item),
                ];
            }
        }

        return $opcoes;
    }

    public static function daLista(Collection $materiais, ?User $usuario): array
    {
        $visiveis = self::visiveis($usuario);
        $vinculos = MaterialVinculo::query()
            ->whereIn('material_id', $materiais->modelKeys())
            ->whereIn('alvo_tipo', array_map(fn (AlvoDeMaterial $a) => $a->value, $visiveis))
            ->get();

        $porMaterial = [];

        foreach ($vinculos->groupBy(fn (MaterialVinculo $v) => $v->alvo_tipo->value) as $tipo => $grupo) {
            $alvo = AlvoDeMaterial::from($tipo);
            $consulta = $alvo->modelo()::withTrashed()->whereKey($grupo->pluck('alvo_id')->all());
            $modelos = ($alvo === AlvoDeMaterial::Show ? $consulta->with('local') : $consulta)->get()->keyBy->getKey();

            foreach ($grupo as $vinculo) {
                if ($modelo = $modelos->get($vinculo->alvo_id)) {
                    $porMaterial[$vinculo->material_id][] = [
                        'id' => $vinculo->id,
                        'tipo' => $alvo->rotulo(),
                        'rotulo' => self::rotulo($modelo),
                    ];
                }
            }
        }

        return $porMaterial;
    }

    public static function de(Model $alvo, ?User $usuario): array
    {
        if (! $usuario?->can('materiais.ver')) {
            return [];
        }

        return $alvo->materiais()->orderBy('ordem')->orderBy('titulo')->get()
            ->map(fn (Material $m) => [
                'uuid' => $m->uuid,
                'titulo' => $m->titulo,
                'tipoRotulo' => $m->tipo->rotulo(),
                'tamanho' => $m->tamanhoLegivel(),
                'url' => $m->link(),
                'noSite' => $m->publico && ! $m->privado,
                'privado' => $m->privado,
            ])->all();
    }

    public static function ligar(Material $material, Model $alvo): void
    {
        $tipo = AlvoDeMaterial::de($alvo);

        MaterialVinculo::firstOrCreate([
            'material_id' => $material->getKey(), 'alvo_tipo' => $tipo->value, 'alvo_id' => $alvo->getKey(),
        ]);

        if ($tipo->reservado()) {
            self::privatizar($material);
        }
    }

    public static function privatizar(Material $material): void
    {
        if ($material->privado) {
            return;
        }

        $origem = $material->arquivo_path;
        $semente = str_starts_with((string) $origem, 'sementes/');
        $destino = $semente ? 'materiais/'.basename((string) $origem) : $origem;
        $conteudo = $semente ? @fopen(public_path((string) $origem), 'r') : Storage::disk('public')->readStream($origem);

        if ($conteudo) {
            Storage::disk('local')->writeStream($destino, $conteudo);
            is_resource($conteudo) && fclose($conteudo);
            $semente || Storage::disk('public')->delete($origem);
        }

        $material->forceFill(['privado' => true, 'publico' => false, 'arquivo_path' => $destino])->save();
    }

    public static function expurgarDosPedidos(array $pedidos): int
    {
        $tipo = AlvoDeMaterial::Contratacao->value;
        $doPedido = fn (Builder $q) => $q->where('alvo_tipo', $tipo)->whereIn('alvo_id', $pedidos);
        $apagados = 0;

        Material::withTrashed()->whereHas('vinculos', $doPedido)->get()->each(function (Material $m) use ($tipo, $pedidos, &$apagados) {
            $resta = $m->vinculos()->where(fn (Builder $q) => $q->where('alvo_tipo', '!=', $tipo)->orWhereNotIn('alvo_id', $pedidos))->exists();

            if (! $resta) {
                Storage::disk($m->privado ? 'local' : 'public')->delete($m->arquivo_path);
                $m->forceDelete();
                $apagados++;
            }
        });

        MaterialVinculo::query()->where($doPedido)->delete();

        return $apagados;
    }
}
