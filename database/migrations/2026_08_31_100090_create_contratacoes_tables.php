<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contratacoes', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->string('nome');
            $table->string('email')->nullable();
            $table->string('telefone', 20)->nullable();

            $table->string('tipo_evento', 30)->default('outro')
                ->comment('bar | aniversario | casamento | empresa | formatura | outro');
            $table->date('data_pretendida')->nullable();
            $table->string('cidade')->nullable();
            $table->string('local')->nullable();
            $table->text('mensagem')->nullable();

            $table->string('origem', 20)->default('site')
                ->comment('site | whatsapp | instagram | indicacao | outro');
            $table->string('status', 20)->default('novo')
                ->comment('novo | em_contato | proposta_enviada | fechado | perdido');

            $table->decimal('valor_proposto', 12, 2)->nullable();
            $table->string('motivo_perda')->nullable();
            $table->foreignId('responsavel_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('show_id')->nullable()->constrained('shows')->nullOnDelete()
                ->comment('Preenchido quando o pedido vira show na agenda — fecha o ciclo');

            $table->timestamp('consentimento_em')->nullable();
            $table->string('consentimento_ip', 45)->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at']);
            $table->index('data_pretendida');
        });

        Schema::create('contratacao_interacoes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contratacao_id')->constrained('contratacoes')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('user_nome')->nullable()
                ->comment('Preservado: o histórico sobrevive à saída de quem escreveu');
            $table->string('tipo', 20)->default('nota')
                ->comment('nota | ligacao | whatsapp | email | reuniao | mudanca_status');
            $table->text('descricao');
            $table->timestampTz('ocorrido_em');
            $table->timestamps();

            $table->index(['contratacao_id', 'ocorrido_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contratacao_interacoes');
        Schema::dropIfExists('contratacoes');
    }
};
