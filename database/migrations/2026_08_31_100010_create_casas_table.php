<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('casas', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('nome');
            $table->string('slug')->unique();
            $table->string('cidade')->nullable();
            $table->string('uf', 2)->nullable();
            $table->string('endereco')->nullable();
            $table->string('bairro')->nullable();
            $table->string('cep', 9)->nullable();

            $table->string('mapa_url', 500)->nullable();
            $table->string('site_url', 500)->nullable();
            $table->string('instagram', 500)->nullable();
            $table->string('logo_path')->nullable();

            $table->string('contato_nome')->nullable();
            $table->string('contato_telefone', 20)->nullable();
            $table->text('observacoes')->nullable()
                ->comment('Interno: cachê praticado, se tem PA, horário de fechamento');

            $table->boolean('ativa')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index('nome');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('casas');
    }
};
