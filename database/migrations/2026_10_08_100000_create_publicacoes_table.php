<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('publicacoes', function (Blueprint $table) {
            $table->id();
            $table->string('tipo', 20)->default('clipping');
            $table->string('titulo');
            $table->string('veiculo')->nullable()->comment('quem publicou: jornal, portal, rádio, canal');
            $table->date('saiu_em')->nullable();
            $table->string('link', 2048);
            $table->text('resumo')->nullable();
            $table->string('imagem_path')->nullable();

            $table->boolean('publicada')->default(false);
            $table->boolean('destaque')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('publicacoes');
    }
};
