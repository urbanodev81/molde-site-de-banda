<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materiais', function (Blueprint $table) {
            $table->boolean('privado')->default(false)->after('publico');
        });

        Schema::create('material_vinculos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained('materiais')->cascadeOnDelete();
            $table->string('alvo_tipo', 20);
            $table->unsignedBigInteger('alvo_id');
            $table->timestamps();

            $table->unique(['material_id', 'alvo_tipo', 'alvo_id']);
            $table->index(['alvo_tipo', 'alvo_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('material_vinculos');

        Schema::table('materiais', function (Blueprint $table) {
            $table->dropColumn('privado');
        });
    }
};
