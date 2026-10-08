<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('musicas', function (Blueprint $table) {
            $table->string('youtube_id', 40)->nullable()->after('estilo');
        });

        Schema::table('videos', function (Blueprint $table) {
            $table->foreignId('show_id')->nullable()->after('casa_nome')->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('videos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('show_id');
        });

        Schema::table('musicas', function (Blueprint $table) {
            $table->dropColumn('youtube_id');
        });
    }
};
