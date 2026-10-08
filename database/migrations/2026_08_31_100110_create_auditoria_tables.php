<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auditorias', function (Blueprint $table) {
            $table->id();
            $table->morphs('auditavel');

            $table->string('evento')->comment('criado | alterado | removido | restaurado');
            $table->string('campo')->nullable();
            $table->text('de')->nullable();
            $table->text('para')->nullable();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_nome')->nullable()
                ->comment('Preservado: a trilha sobrevive à anonimização do autor');
            $table->string('ip', 45)->nullable();
            $table->string('user_agent')->nullable();

            $table->timestampTz('ocorreu_em');
            $table->timestamp('created_at')->nullable();

            $table->index('ocorreu_em');
            $table->index(['user_id', 'ocorreu_em']);
        });

        Schema::create('politicas_retencao', function (Blueprint $table) {
            $table->id();
            $table->string('recurso')->unique()
                ->comment('auditoria | contratacao | contratacao_interacao');
            $table->unsignedSmallInteger('meses');
            $table->text('justificativa')->nullable();
            $table->boolean('nunca_expurgar')->default(false)
                ->comment('Consentimento e prova de atendimento ao titular: a defesa da casa');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('politicas_retencao');
        Schema::dropIfExists('auditorias');
    }
};
