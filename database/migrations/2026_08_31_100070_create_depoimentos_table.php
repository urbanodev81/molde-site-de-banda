<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('depoimentos', function (Blueprint $table) {
            $table->id();
            $table->string('autor');
            $table->string('papel')->nullable()->comment('"dono do Casa da Esquina", "aniversariante"');
            $table->text('texto');
            $table->foreignId('show_id')->nullable()->constrained('shows')->nullOnDelete();
            $table->date('ocorrido_em')->nullable();

            $table->boolean('autorizado')->default(false);
            $table->boolean('publicado')->default(false);
            $table->unsignedSmallInteger('ordem')->default(0);

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('depoimentos');
    }
};
