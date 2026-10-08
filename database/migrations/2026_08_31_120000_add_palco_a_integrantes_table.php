<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('integrantes', function (Blueprint $table) {
            $table->unsignedTinyInteger('palco_esquerda')->default(20)
                ->comment('%, a partir da borda esquerda do palco');
            $table->unsignedTinyInteger('palco_largura')->default(24)
                ->comment('%, largura do recorte em relação ao palco');
            $table->unsignedTinyInteger('palco_base')->default(0)
                ->comment('%, distância do chão do palco');
        });
    }

    public function down(): void
    {
        Schema::table('integrantes', function (Blueprint $table) {
            $table->dropColumn(['palco_esquerda', 'palco_largura', 'palco_base']);
        });
    }
};
