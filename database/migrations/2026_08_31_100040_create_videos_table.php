<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->string('titulo');
            $table->foreignId('casa_id')->nullable()->constrained('casas')->nullOnDelete();
            $table->string('casa_nome')->nullable()
                ->comment('Quando a casa não está cadastrada: "Estúdio · São Paulo"');
            $table->date('gravado_em')->nullable();

            $table->string('tipo', 20)->default('arquivo')->comment('arquivo | youtube');
            $table->string('youtube_id', 40)->nullable();
            $table->string('arquivo_webm_path')->nullable();
            $table->string('arquivo_mp4_path')->nullable();
            $table->string('capa_path')->nullable();

            $table->boolean('demonstracao')->default(false);
            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('publicado')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['publicado', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
