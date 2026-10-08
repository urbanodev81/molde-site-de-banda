<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('materiais', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();
            $table->string('titulo');
            $table->text('descricao')->nullable();
            $table->string('tipo', 30)->default('outro')
                ->comment('foto_alta | logo | rider | mapa_palco | contrato | outro');
            $table->string('arquivo_path');
            $table->string('arquivo_nome_original')->nullable();
            $table->unsignedInteger('tamanho_bytes')->nullable();
            $table->boolean('publico')->default(false);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('materiais');
    }
};
