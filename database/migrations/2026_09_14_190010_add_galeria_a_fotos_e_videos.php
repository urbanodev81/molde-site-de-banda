<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fotos', function (Blueprint $table) {
            $table->foreignId('tipo_galeria_id')->nullable()->after('integrante_id')
                ->constrained('tipos_galeria')->nullOnDelete();
            $table->boolean('destaque')->default(false)->after('publicada');
            $table->date('registrada_em')->nullable()->after('credito');

            $table->index(['publicada', 'destaque']);
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('tipo_galeria_id')->nullable()->after('show_id')
                ->constrained('tipos_galeria')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tipo_galeria_id');
        });

        Schema::table('fotos', function (Blueprint $table) {
            $table->dropIndex(['publicada', 'destaque']);
            $table->dropConstrainedForeignId('tipo_galeria_id');
            $table->dropColumn(['destaque', 'registrada_em']);
        });
    }
};
