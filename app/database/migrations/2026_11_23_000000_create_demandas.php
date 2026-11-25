<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandas', function (Blueprint $table) {
            $table->id();
            $table->string('protocolo', 21)->unique();
            $table->string('servico');
            $table->text('descricao');
            $table->decimal('latitude', 9, 6);
            $table->decimal('longitude', 9, 6);
            $table->string('status');
            $table->timestamp('prazo');
            $table->timestamps();
        });
        Schema::create('historico_status', function (Blueprint $table) {
            $table->id();
            $table->foreignId('demanda_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->boolean('notificacao_simulada')->default(false);
            $table->timestamp('ocorrido_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('historico_status');
        Schema::dropIfExists('demandas');
    }
};
