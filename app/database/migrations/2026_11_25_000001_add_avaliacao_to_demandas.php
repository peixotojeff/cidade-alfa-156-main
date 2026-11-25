<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Avaliação do cidadão após a resolução: excelente, bom ou pessimo (uma por chamado).
 * Base do NPA (% Excelente − % Péssimo) exibido em /indicadores.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->string('avaliacao', 10)->nullable();
            $table->timestamp('avaliada_em')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->dropColumn(['avaliacao', 'avaliada_em']);
        });
    }
};
