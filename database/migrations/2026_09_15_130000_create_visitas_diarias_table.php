<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('visitas_diarias', function (Blueprint $table) {
            $table->id();

            $table->date('data');

            $table->string('caminho', 255);

            $table->string('rota', 100)->nullable();

            $table->unsignedInteger('visitas')->default(0);

            $table->timestamps();

            $table->unique(['data', 'caminho']);
            $table->index('data');
            $table->index(['rota', 'data']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('visitas_diarias');
    }
};
