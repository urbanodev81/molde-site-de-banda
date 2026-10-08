<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('central_outbox', function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('tipo', 60);
            $table->json('payload');
            $table->timestamp('ocorreu_em');

            $table->unsignedSmallInteger('tentativas')->default(0);
            $table->timestamp('proxima_tentativa_em');
            $table->text('ultimo_erro')->nullable();

            $table->timestamp('descartado_em')->nullable();
            $table->timestamps();

            $table->index(['descartado_em', 'proxima_tentativa_em']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('central_outbox');
    }
};
