<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrantes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('nome');
            $table->string('nome_artistico')->nullable();
            $table->string('instrumento')->nullable();
            $table->text('bio')->nullable();

            $table->string('foto_path')->nullable();
            $table->string('recorte_path')->nullable();

            $table->string('instagram', 500)->nullable();
            $table->unsignedSmallInteger('ordem')->default(0)
                ->comment('Ordem em que entram no palco da home. A banda decide.');
            $table->boolean('ativa')->default(true);

            $table->date('autorizacao_imagem_em')->nullable()
                ->comment('Sem isto ela NÃO vai para o site. Ver o cabeçalho da migration.');
            $table->string('autorizacao_documento_path')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrantes');
    }
};
