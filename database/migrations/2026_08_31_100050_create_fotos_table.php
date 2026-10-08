<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fotos', function (Blueprint $table) {
            $table->id();
            $table->uuid()->unique();

            $table->string('legenda')->nullable();
            $table->string('arquivo_path');
            $table->foreignId('show_id')->nullable()->constrained('shows')->nullOnDelete();
            $table->foreignId('integrante_id')->nullable()->constrained('integrantes')->nullOnDelete();

            $table->string('credito')->nullable();
            $table->unsignedSmallInteger('largura')->nullable();
            $table->unsignedSmallInteger('altura')->nullable();

            $table->unsignedSmallInteger('ordem')->default(0);
            $table->boolean('publicada')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['publicada', 'ordem']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fotos');
    }
};
