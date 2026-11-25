<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Endereço do local atendido e origem do registro.
 * - origem = 'cidadao' (aberto no app) ou 'sp156' (amostra dos dados abertos do SP156, CC0).
 * - finalizada_em: data de conclusão, base do indicador de resolutividade no prazo (SLA).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('demandas', function (Blueprint $table) {
            $table->string('logradouro')->nullable();
            $table->string('numero', 20)->nullable();
            $table->string('distrito')->nullable();
            $table->string('subprefeitura')->nullable();
            $table->string('origem', 20)->default('cidadao');
            $table->timestamp('finalizada_em')->nullable();
            $table->index(['origem']);
            $table->index(['servico', 'status']);
        });
        // FK sem índice no Postgres: usada na página do chamado e no delete em cascata.
        Schema::table('historico_status', function (Blueprint $table) {
            $table->index('demanda_id');
        });
    }

    public function down(): void
    {
        Schema::table('historico_status', function (Blueprint $table) {
            $table->dropIndex(['demanda_id']);
        });
        Schema::table('demandas', function (Blueprint $table) {
            $table->dropIndex(['origem']);
            $table->dropIndex(['servico', 'status']);
            $table->dropColumn(['logradouro', 'numero', 'distrito', 'subprefeitura', 'origem', 'finalizada_em']);
        });
    }
};
