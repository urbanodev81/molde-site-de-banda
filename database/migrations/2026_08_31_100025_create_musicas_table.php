<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('musicas', function (Blueprint $table) {
            $table->id();
            $table->string('titulo');
            $table->string('artista')->nullable();
            $table->unsignedSmallInteger('ano')->nullable();
            $table->string('estilo')->nullable()->comment('rock nacional, pop rock, anos 80...');
            $table->string('tom', 10)->nullable();
            $table->unsignedSmallInteger('duracao_segundos')->nullable();

            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('publicada')->default(true);
            $table->boolean('destaque')->default(false)
                ->comment('Aparece na amostra curta da home; a lista completa fica na página do repertório');
            $table->text('observacoes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('titulo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('musicas');
    }
};
