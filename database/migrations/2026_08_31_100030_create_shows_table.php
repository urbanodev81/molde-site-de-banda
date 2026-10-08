<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shows', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->foreignId('casa_id')->nullable()->constrained('casas')->nullOnDelete();

            $table->string('titulo')->nullable()
                ->comment('Usado quando não há casa: "Aniversário · evento particular"');
            $table->string('endereco_livre')->nullable();
            $table->string('mapa_url', 500)->nullable();

            $table->timestampTz('comeca_em');
            $table->timestampTz('termina_em')->nullable();

            $table->string('status', 20)->default('rascunho')
                ->comment('rascunho | confirmado | cancelado | realizado');
            $table->string('tipo', 20)->default('publico')
                ->comment('publico | particular — o particular NUNCA vai para o site');

            $table->string('entrada')->nullable()
                ->comment('"Entrada franca", "Couvert R$ 20" — texto, porque cada casa diz do seu jeito');
            $table->string('cartaz_path')->nullable();

            $table->text('observacoes_publicas')->nullable();
            $table->text('observacoes_internas')->nullable()
                ->comment('Cachê, contato do dia, o que levar. Nunca sai no site.');

            $table->decimal('cache', 12, 2)->nullable()
                ->comment('decimal(12,2) — dinheiro nunca em float (PADROES.md)');

            $table->boolean('destaque')->default(false);
            $table->boolean('publicado')->default(true)
                ->comment('Publicado E confirmado E público E futuro = aparece na agenda');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['comeca_em', 'status']);
            $table->index('publicado');
        });

        Schema::create('musica_show', function (Blueprint $table) {
            $table->id();
            $table->foreignId('show_id')->constrained('shows')->cascadeOnDelete();
            $table->foreignId('musica_id')->constrained('musicas')->cascadeOnDelete();
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->string('bloco', 40)->nullable()->comment('"1º set", "bis"');
            $table->timestamps();

            $table->unique(['show_id', 'musica_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('musica_show');
        Schema::dropIfExists('shows');
    }
};
