<?php

declare(strict_types=1);

namespace App\Support\Lote;

use App\Models\Contratacao;
use App\Models\Depoimento;
use App\Models\Integrante;
use App\Models\Local;
use App\Models\Material;
use App\Models\Musica;
use App\Models\ParticipacaoEspecial;
use App\Models\Pergunta;
use App\Models\Publicacao;
use App\Models\Show;
use App\Models\TipoEspaco;
use App\Models\TipoGaleria;
use App\Models\User;
use App\Models\Video;
use App\Support\Arquivos;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

final class ListasEmLote
{
    public static function todas(): array
    {
        return [
            'shows' => [
                'modelo' => Show::class,
                'ver' => 'shows.ver',
                'gerenciar' => 'shows.gerenciar',
                'nome' => ['show', 'shows', 'o'],
                'coluna' => 'publicado',
                'ligado' => 'Publicado',
                'desligado' => 'Fora do site',
                'ordem' => fn (Builder $q) => $q->with('local')->orderBy('comeca_em'),
                'linha' => fn (Show $s) => [
                    'titulo' => $s->nome(),
                    'detalhe' => $s->comeca_em?->format('d/m/Y H:i'),
                    'link' => $s->trashed() ? null : route('site.show.uuid', $s->uuid),
                ],
                'publicos' => fn (Builder $q) => $q->publicaveis(),
            ],
            'locais' => [
                'modelo' => Local::class,
                'ver' => 'locais.ver',
                'gerenciar' => 'locais.gerenciar',
                'nome' => ['local', 'locais', 'o'],
                'coluna' => 'ativa',
                'ligado' => 'Ativo',
                'desligado' => 'Inativo',
                'ordem' => fn (Builder $q) => $q->orderBy('nome'),
                'linha' => fn (Local $l) => ['titulo' => $l->nome, 'detalhe' => $l->enderecoCompleto(), 'link' => null],
                'publicos' => null,
            ],
            'integrantes' => [
                'modelo' => Integrante::class,
                'ver' => 'integrantes.ver',
                'gerenciar' => 'integrantes.gerenciar',
                'nome' => ['integrante', 'integrantes', 'a'],
                'coluna' => 'ativa',
                'ligado' => 'Ativa',
                'desligado' => 'Inativa',
                'ordem' => fn (Builder $q) => $q->orderBy('ordem'),
                'linha' => fn (Integrante $i) => ['titulo' => $i->comoAparece(), 'detalhe' => $i->instrumento, 'link' => null],

                'publicos' => null,
            ],
            'participacoes' => [
                'modelo' => ParticipacaoEspecial::class,
                'ver' => 'integrantes.ver',
                'gerenciar' => 'integrantes.gerenciar',
                'nome' => ['participação', 'participações', 'a'],
                'coluna' => 'publicada',
                'ligado' => 'Publicada',
                'desligado' => 'Não publicada',
                'ordem' => fn (Builder $q) => $q->orderBy('ordem')->orderBy('nome'),
                'linha' => fn (ParticipacaoEspecial $p) => ['titulo' => $p->nome, 'detalhe' => $p->funcao, 'link' => null],
                'publicos' => null,
            ],
            'videos' => [
                'modelo' => Video::class,
                'ver' => 'videos.ver',
                'gerenciar' => 'videos.gerenciar',
                'nome' => ['vídeo', 'vídeos', 'o'],
                'coluna' => 'publicado',
                'ligado' => 'Publicado',
                'desligado' => 'Fora do site',
                'ordem' => fn (Builder $q) => $q->orderBy('ordem'),
                'linha' => fn (Video $v) => [
                    'titulo' => $v->titulo,
                    'detalhe' => $v->ondeFoi(),
                    'link' => $v->linkDeOrigem() ?? route('site.galeria'),
                ],
                'publicos' => fn (Builder $q) => $q->doSite(),
            ],
            'musicas' => [
                'modelo' => Musica::class,
                'chave' => 'id',
                'ver' => 'musicas.ver',
                'gerenciar' => 'musicas.gerenciar',
                'nome' => ['música', 'músicas', 'a'],
                'coluna' => 'publicada',
                'ligado' => 'Publicada',
                'desligado' => 'Fora do site',
                'ordem' => fn (Builder $q) => $q->orderBy('ordem')->orderBy('titulo'),
                'linha' => fn (Musica $m) => ['titulo' => $m->titulo, 'detalhe' => $m->artista, 'link' => null],
                'publicos' => fn (Builder $q) => $q->where('publicada', true),
            ],
            'tipos-galeria' => [
                'modelo' => TipoGaleria::class,
                'chave' => 'id',

                'ver' => 'fotos.ver',
                'gerenciar' => 'fotos.gerenciar',
                'nome' => ['tipo', 'tipos', 'o'],
                'coluna' => 'publicado',
                'ligado' => 'Publicado',
                'desligado' => 'Fora do site',
                'ordem' => fn (Builder $q) => $q->orderBy('ordem')->orderBy('nome'),
                'linha' => fn (TipoGaleria $t) => ['titulo' => $t->nome, 'detalhe' => $t->descricao, 'link' => null],
                'publicos' => null,

                'intocavel' => fn (Model $t, Request $r, string $acao) => $acao === 'arquivar' && $t->ehDeShows(),
                'aviso' => 'O tipo "Shows" não é arquivado: é ele que junta as fotos e vídeos de todas as noites.',
            ],
            'tipos-espaco' => [
                'modelo' => TipoEspaco::class,
                'chave' => 'id',

                'ver' => 'locais.ver',
                'gerenciar' => 'locais.gerenciar',
                'nome' => ['tipo', 'tipos', 'o'],
                'coluna' => 'publicado',
                'ligado' => 'No site',
                'desligado' => 'Só no painel',
                'ordem' => fn (Builder $q) => $q->orderBy('ordem')->orderBy('nome'),
                'linha' => fn (TipoEspaco $t) => ['titulo' => $t->nome, 'detalhe' => $t->descricao, 'link' => null],
                'publicos' => null,
            ],
            'perguntas' => [
                'modelo' => Pergunta::class,
                'chave' => 'id',

                'ver' => 'perguntas.gerenciar',
                'gerenciar' => 'perguntas.gerenciar',
                'nome' => ['dúvida', 'dúvidas', 'a'],
                'coluna' => 'publicada',
                'ligado' => 'Publicada',
                'desligado' => 'Não publicada',
                'ordem' => fn (Builder $q) => $q->orderBy('ordem'),
                'linha' => fn (Pergunta $p) => ['titulo' => $p->pergunta, 'detalhe' => $p->resposta, 'link' => null],
                'publicos' => null,
            ],
            'depoimentos' => [
                'modelo' => Depoimento::class,
                'chave' => 'id',
                'ver' => 'depoimentos.gerenciar',
                'gerenciar' => 'depoimentos.gerenciar',
                'nome' => ['depoimento', 'depoimentos', 'o'],
                'coluna' => 'publicado',
                'ligado' => 'Publicado',
                'desligado' => 'Não publicado',
                'ordem' => fn (Builder $q) => $q->orderBy('ordem'),
                'linha' => fn (Depoimento $d) => ['titulo' => $d->autor, 'detalhe' => $d->texto, 'link' => null],

                'publicos' => null,
            ],
            'publicacoes' => [
                'modelo' => Publicacao::class,
                'chave' => 'id',

                'ver' => 'publicacoes.gerenciar',
                'gerenciar' => 'publicacoes.gerenciar',
                'nome' => ['publicação', 'publicações', 'a'],
                'coluna' => 'publicada',
                'ligado' => 'Publicada',
                'desligado' => 'Fora do site',
                'ordem' => fn (Builder $q) => $q->orderByRaw('saiu_em desc nulls last')->orderByDesc('id'),
                'linha' => fn (Publicacao $p) => ['titulo' => $p->titulo, 'detalhe' => $p->origem(), 'link' => $p->link],

                'publicos' => fn (Builder $q) => $q->publicaveis(),
            ],
            'materiais' => [
                'modelo' => Material::class,
                'ver' => 'materiais.ver',
                'gerenciar' => 'materiais.gerenciar',
                'nome' => ['material', 'materiais', 'o'],
                'coluna' => 'publico',
                'ligado' => 'Público',
                'desligado' => 'Só no painel',
                'ordem' => fn (Builder $q) => $q->orderBy('ordem')->orderBy('titulo'),
                'linha' => fn (Material $m) => [
                    'titulo' => $m->titulo,
                    'detalhe' => $m->descricao,
                    'link' => $m->privado ? null : Arquivos::url($m->arquivo_path),
                ],
                'publicos' => fn (Builder $q) => $q->where('publico', true)->where('privado', false),

                'intocavel' => fn (Model $m, Request $r, string $acao) => $acao === 'ligar' && $m->privado,
                'aviso' => 'Material ligado a pedido de contratação é privado e não vai ao site.',
            ],
            'contratacoes' => [
                'modelo' => Contratacao::class,
                'ver' => 'contratacoes.ver',
                'gerenciar' => 'contratacoes.gerenciar',
                'nome' => ['pedido', 'pedidos', 'o'],

                'coluna' => null,
                'ordem' => fn (Builder $q) => $q->orderBy('created_at'),

                'linha' => fn (Contratacao $c) => [
                    'titulo' => $c->nome,
                    'detalhe' => trim($c->tipo_evento->rotulo().' · '.($c->data_pretendida?->format('d/m/Y') ?? 'sem data').' · '.$c->status->rotulo()),
                    'link' => null,
                ],
                'publicos' => null,
            ],
            'usuarios' => [
                'modelo' => User::class,
                'ver' => 'usuarios.ver',
                'gerenciar' => 'usuarios.gerenciar',
                'nome' => ['conta', 'contas', 'a'],
                'coluna' => 'ativo',
                'ligado' => 'Ativa',
                'desligado' => 'Inativa',
                'ordem' => fn (Builder $q) => $q->orderBy('name'),
                'linha' => fn (User $u) => ['titulo' => $u->name, 'detalhe' => $u->email, 'link' => null],
                'publicos' => null,

                'intocavel' => fn (Model $u, Request $r, string $acao) => $u->getKey() === $r->user()?->getKey(),
                'aviso' => 'A sua própria conta ficou como estava.',
            ],
        ];
    }

    public static function de(string $lista): array
    {
        return self::todas()[$lista] ?? abort(404);
    }

    public static function chave(array $lista): string
    {
        return $lista['chave'] ?? 'uuid';
    }

    public static function regraDaChave(array $lista): string
    {
        return self::chave($lista) === 'id' ? 'integer' : 'uuid';
    }

    public static function abas(string $lista, Request $request): array
    {
        $modelo = self::de($lista)['modelo'];

        return [
            'arquivados' => $request->boolean('arquivados'),
            'totais' => [$modelo::query()->count(), $modelo::onlyTrashed()->count()],
        ];
    }

    public static function frase(array $lista, int $quantas, string $estado): string
    {
        [$um, $varios, $genero] = $lista['nome'];

        if ($quantas === 0) {
            return $genero === 'a' ? "Nenhuma {$um} precisou ser alterada." : "Nenhum {$um} precisou ser alterado.";
        }

        return $quantas === 1 ? "1 {$um} {$estado}{$genero}." : "{$quantas} {$varios} {$estado}{$genero}s.";
    }

    public static function situacao(array $lista, Model $item): string
    {
        if ($item->trashed()) {
            return $lista['nome'][2] === 'a' ? 'Arquivada' : 'Arquivado';
        }

        if ($lista['coluna'] === null) {
            return '';
        }

        return $item->{$lista['coluna']} ? $lista['ligado'] : $lista['desligado'];
    }
}
