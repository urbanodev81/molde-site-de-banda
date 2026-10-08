<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('participacoes_especiais', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->string('nome');
            $table->string('funcao')->nullable()->comment('Guitarra, voz, sax…');
            $table->text('descricao')->nullable();
            $table->string('foto_path')->nullable();

            $table->string('instagram', 500)->nullable();
            $table->string('facebook', 500)->nullable();
            $table->string('tiktok', 500)->nullable();
            $table->string('youtube', 500)->nullable();
            $table->string('site_url', 500)->nullable();

            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('publicada')->default(true);

            $table->date('autorizacao_imagem_em')->nullable()
                ->comment('Sem isto a participação NÃO vai para o site. Ver o cabeçalho da migration.');
            $table->string('autorizacao_documento_path')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('participacao_especial_show', function (Blueprint $table) {
            $table->foreignId('participacao_especial_id')->constrained('participacoes_especiais')->cascadeOnDelete();
            $table->foreignId('show_id')->constrained('shows')->cascadeOnDelete();
            $table->primary(['participacao_especial_id', 'show_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('participacao_especial_show');
        Schema::dropIfExists('participacoes_especiais');
    }
};
