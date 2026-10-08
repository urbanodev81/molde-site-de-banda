<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const ESPACOS = [
        ['bar', 'Bar'],
        ['casa-de-show', 'Casa de show'],
        ['restaurante', 'Restaurante'],
        ['teatro', 'Teatro'],
        ['praca', 'Praça'],
        ['festival', 'Festival'],
        ['casa-de-festas', 'Casa de festas'],
        ['clube', 'Clube'],
    ];

    public function up(): void
    {
        Schema::rename('tipos_galeria', 'tipos');

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER INDEX IF EXISTS tipos_galeria_pkey RENAME TO tipos_pkey');
            DB::statement('ALTER INDEX IF EXISTS tipos_galeria_publicado_ordem_index RENAME TO tipos_publicado_ordem_index');
            DB::statement('ALTER SEQUENCE IF EXISTS tipos_galeria_id_seq RENAME TO tipos_id_seq');
        }

        Schema::table('tipos', function (Blueprint $table) {
            $table->string('grupo', 20)->default('galeria')->after('id');
            $table->dropUnique('tipos_galeria_slug_unique');
            $table->unique(['grupo', 'slug']);
        });

        Schema::table('locais', function (Blueprint $table) {
            $table->foreignId('tipo_id')->nullable()->after('slug')->constrained('tipos')->nullOnDelete();
        });

        $agora = now();

        DB::table('tipos')->insert(array_map(fn (array $t, int $ordem) => [
            'grupo' => 'espaco', 'slug' => $t[0], 'nome' => $t[1], 'ordem' => $ordem,
            'publicado' => true, 'created_at' => $agora, 'updated_at' => $agora,
        ], self::ESPACOS, array_keys(self::ESPACOS)));
    }

    public function down(): void
    {
        Schema::table('locais', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_id');
        });

        DB::table('tipos')->where('grupo', '!=', 'galeria')->delete();

        Schema::table('tipos', function (Blueprint $table) {
            $table->dropUnique(['grupo', 'slug']);
            $table->dropColumn('grupo');
            $table->unique('slug', 'tipos_galeria_slug_unique');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER INDEX IF EXISTS tipos_pkey RENAME TO tipos_galeria_pkey');
            DB::statement('ALTER INDEX IF EXISTS tipos_publicado_ordem_index RENAME TO tipos_galeria_publicado_ordem_index');
            DB::statement('ALTER SEQUENCE IF EXISTS tipos_id_seq RENAME TO tipos_galeria_id_seq');
        }

        Schema::rename('tipos', 'tipos_galeria');
    }
};
