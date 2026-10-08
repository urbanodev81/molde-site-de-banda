<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->string('link_url', 500)->nullable()->after('youtube_id');
            $table->unsignedSmallInteger('duracao_segundos')->nullable()->after('arquivo_mp4_path');
            $table->unsignedInteger('tamanho_bytes')->nullable()->after('duracao_segundos');
        });

        Schema::table('musicas', function (Blueprint $table) {
            $table->foreignId('video_id')->nullable()->after('youtube_id')->constrained('videos')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('musicas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('video_id');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->dropColumn(['link_url', 'duracao_segundos', 'tamanho_bytes']);
        });
    }
};
