<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('foto_integrante', function (Blueprint $table) {
            $table->foreignId('foto_id')->constrained('fotos')->cascadeOnDelete();
            $table->foreignId('integrante_id')->constrained('integrantes')->cascadeOnDelete();
            $table->primary(['foto_id', 'integrante_id']);
        });

        Schema::create('integrante_video', function (Blueprint $table) {
            $table->foreignId('integrante_id')->constrained('integrantes')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->primary(['integrante_id', 'video_id']);
        });

        DB::table('foto_integrante')->insertUsing(
            ['foto_id', 'integrante_id'],
            DB::table('fotos')->whereNotNull('integrante_id')->select('id', 'integrante_id'),
        );

        Schema::table('fotos', function (Blueprint $table) {
            $table->dropConstrainedForeignId('integrante_id');
        });
    }

    public function down(): void
    {
        Schema::table('fotos', function (Blueprint $table) {
            $table->foreignId('integrante_id')->nullable()->after('show_id')
                ->constrained('integrantes')->nullOnDelete();
        });

        foreach (DB::table('foto_integrante')->orderBy('integrante_id')->get() as $par) {
            DB::table('fotos')->where('id', $par->foto_id)->whereNull('integrante_id')
                ->update(['integrante_id' => $par->integrante_id]);
        }

        Schema::dropIfExists('integrante_video');
        Schema::dropIfExists('foto_integrante');
    }
};
