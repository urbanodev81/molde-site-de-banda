<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipos_galeria', function (Blueprint $table) {
            $table->id();

            $table->string('nome');
            $table->string('slug')->unique();

            $table->string('descricao')->nullable();

            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('publicado')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['publicado', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipos_galeria');
    }
};
