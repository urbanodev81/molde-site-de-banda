<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Perfis;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PerfisESeguranca extends Seeder
{
    private const PERMISSOES = [
        'Agenda' => [
            'shows.ver',
            'shows.gerenciar',
            'locais.ver',
            'locais.gerenciar',
        ],
        'Conteúdo do site' => [
            'integrantes.ver',
            'integrantes.gerenciar',
            'videos.ver',
            'videos.gerenciar',
            'fotos.ver',
            'fotos.gerenciar',
            'musicas.ver',
            'musicas.gerenciar',
            'perguntas.gerenciar',
            'depoimentos.gerenciar',
            'publicacoes.gerenciar',
            'materiais.ver',
            'materiais.gerenciar',
        ],
        'Contratação' => [
            'contratacoes.ver',
            'contratacoes.gerenciar',

            'contratacoes.ver_valores',
        ],

        'Audiência' => [
            'estatisticas.ver',
        ],

        'Administração' => [
            'configuracoes.gerenciar',
            'usuarios.ver',
            'usuarios.gerenciar',
            'auditoria.ver',
        ],
    ];

    private const PERFIS = [
        Perfis::ADMINISTRADOR => '*',

        Perfis::BANDA => [
            'shows.ver', 'shows.gerenciar',
            'locais.ver', 'locais.gerenciar',
            'integrantes.ver', 'integrantes.gerenciar',
            'videos.ver', 'videos.gerenciar',
            'fotos.ver', 'fotos.gerenciar',
            'musicas.ver', 'musicas.gerenciar',
            'perguntas.gerenciar',
            'depoimentos.gerenciar',
            'publicacoes.gerenciar',
            'materiais.ver', 'materiais.gerenciar',
            'contratacoes.ver', 'contratacoes.gerenciar',

            'estatisticas.ver',
        ],

        Perfis::PRODUCAO => [
            'shows.ver', 'shows.gerenciar',
            'locais.ver', 'locais.gerenciar',
            'contratacoes.ver', 'contratacoes.gerenciar', 'contratacoes.ver_valores',
            'materiais.ver',
            'musicas.ver',

            'estatisticas.ver',
        ],
    ];

    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $todas = [];

        foreach (self::PERMISSOES as $permissoes) {
            foreach ($permissoes as $nome) {
                Permission::findOrCreate($nome, 'web');
                $todas[] = $nome;
            }
        }

        foreach (self::PERFIS as $perfil => $permissoes) {
            $papel = Role::findOrCreate($perfil, 'web');

            $papel->syncPermissions($permissoes === '*' ? $todas : $permissoes);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function grupos(): array
    {
        return self::PERMISSOES;
    }
}
