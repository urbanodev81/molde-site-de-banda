<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->string('slug', 160)->nullable()->unique()->after('uuid');
        });

        $shows = DB::table('shows')
            ->leftJoin('casas', 'casas.id', '=', 'shows.casa_id')
            ->orderBy('shows.comeca_em')
            ->orderBy('shows.id')
            ->get(['shows.id', 'shows.comeca_em', 'shows.titulo', 'casas.nome as casa_nome']);

        foreach ($shows as $show) {
            DB::table('shows')
                ->where('id', $show->id)
                ->update(['slug' => $this->slugDisponivel($show)]);
        }
    }

    public function down(): void
    {
        Schema::table('shows', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }

    private function slugDisponivel(object $show): string
    {
        $data = $show->comeca_em ? Carbon::parse($show->comeca_em) : null;
        $nome = $show->casa_nome ?? $show->titulo ?? 'Show';
        $mes = Str::slug($data?->translatedFormat('F-Y') ?? 'sem-data');

        $base = Str::limit('show-'.Str::slug($nome), 120, '').'-'.$mes;
        $comDia = 'show-'.Str::slug($nome).'-'.($data?->format('d') ?? '00').'-'.$mes;

        $candidatos = [$base, $comDia];

        foreach (range(2, 50) as $n) {
            $candidatos[] = $comDia.'-'.$n;
        }

        foreach ($candidatos as $candidato) {
            $ocupado = DB::table('shows')
                ->where('slug', $candidato)
                ->where('id', '!=', $show->id)
                ->exists();

            if (! $ocupado) {
                return $candidato;
            }
        }

        return $comDia.'-'.Str::lower(Str::random(6));
    }
};
