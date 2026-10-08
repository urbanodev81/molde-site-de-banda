<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    private const PERMISSOES = [
        'casas.ver' => 'locais.ver',
        'casas.gerenciar' => 'locais.gerenciar',
    ];

    private const SEMENTES = [
        'sementes/casa-casa-da-esquina.webp' => 'sementes/local-casa-da-esquina.webp',
        'sementes/casa-bar-do-centro.webp' => 'sementes/local-bar-do-centro.webp',
    ];

    public function up(): void
    {
        Schema::rename('casas', 'locais');

        Schema::table('shows', function (Blueprint $table) {
            $table->renameColumn('casa_id', 'local_id');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->renameColumn('casa_id', 'local_id');
            $table->renameColumn('casa_nome', 'local_nome');
        });

        $this->renomearObjetos([
            'casas_id_seq' => 'locais_id_seq',
            'casas_pkey' => 'locais_pkey',
            'casas_uuid_unique' => 'locais_uuid_unique',
            'casas_slug_unique' => 'locais_slug_unique',
            'casas_nome_index' => 'locais_nome_index',
            'shows_casa_id_foreign' => 'shows_local_id_foreign',
            'videos_casa_id_foreign' => 'videos_local_id_foreign',
        ]);

        $this->migrarDados('locais', self::PERMISSOES, self::SEMENTES, 'App\Models\Casa', 'App\Models\Local', 'casas/', 'locais/');
    }

    public function down(): void
    {
        Schema::rename('locais', 'casas');

        Schema::table('shows', function (Blueprint $table) {
            $table->renameColumn('local_id', 'casa_id');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->renameColumn('local_id', 'casa_id');
            $table->renameColumn('local_nome', 'casa_nome');
        });

        $this->renomearObjetos([
            'locais_id_seq' => 'casas_id_seq',
            'locais_pkey' => 'casas_pkey',
            'locais_uuid_unique' => 'casas_uuid_unique',
            'locais_slug_unique' => 'casas_slug_unique',
            'locais_nome_index' => 'casas_nome_index',
            'shows_local_id_foreign' => 'shows_casa_id_foreign',
            'videos_local_id_foreign' => 'videos_casa_id_foreign',
        ]);

        $this->migrarDados(
            'casas',
            array_flip(self::PERMISSOES),
            array_flip(self::SEMENTES),
            'App\Models\Local',
            'App\Models\Casa',
            'locais/',
            'casas/'
        );
    }

    private function renomearObjetos(array $mapa): void
    {
        foreach ($mapa as $de => $para) {
            if (str_ends_with($de, '_seq')) {
                $existe = DB::selectOne('select 1 as ok from pg_sequences where sequencename = ?', [$de]);
                if ($existe) {
                    DB::statement(sprintf('ALTER SEQUENCE %s RENAME TO %s', $de, $para));
                }

                continue;
            }

            $constraint = DB::selectOne('select conrelid::regclass as tabela from pg_constraint where conname = ?', [$de]);

            if ($constraint) {
                DB::statement(sprintf('ALTER TABLE %s RENAME CONSTRAINT %s TO %s', $constraint->tabela, $de, $para));

                continue;
            }

            $indice = DB::selectOne('select 1 as ok from pg_indexes where indexname = ?', [$de]);

            if ($indice) {
                DB::statement(sprintf('ALTER INDEX %s RENAME TO %s', $de, $para));
            }
        }
    }

    private function migrarDados(
        string $tabela,
        array $permissoes,
        array $sementes,
        string $classeDe,
        string $classePara,
        string $pastaDe,
        string $pastaPara,
    ): void {
        if (Schema::hasTable('permissions')) {
            foreach ($permissoes as $de => $para) {
                DB::table('permissions')->where('name', $de)->update(['name' => $para]);
            }
        }

        if (Schema::hasTable('auditorias')) {
            DB::table('auditorias')->where('auditavel_type', $classeDe)->update(['auditavel_type' => $classePara]);
        }

        foreach ($sementes as $de => $para) {
            DB::table($tabela)->where('logo_path', $de)->update(['logo_path' => $para]);
        }

        DB::table($tabela)
            ->where('logo_path', 'like', $pastaDe.'%')
            ->update(['logo_path' => DB::raw(sprintf("replace(logo_path, '%s', '%s')", $pastaDe, $pastaPara))]);

        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
