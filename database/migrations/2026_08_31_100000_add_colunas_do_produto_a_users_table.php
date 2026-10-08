<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->unique()->after('id');
            $table->string('telefone', 20)->nullable()->after('email');
            $table->boolean('ativo')->default(true)->after('telefone');
            $table->timestamp('ultimo_acesso_em')->nullable()->after('ativo');
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropSoftDeletes();
            $table->dropColumn(['uuid', 'telefone', 'ativo', 'ultimo_acesso_em']);
        });
    }
};
