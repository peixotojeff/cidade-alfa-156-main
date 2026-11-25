<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Demanda extends Model
{
    protected $fillable = ['protocolo', 'servico', 'descricao', 'latitude', 'longitude', 'status', 'prazo'];

    protected function casts(): array
    {
        return ['prazo' => 'datetime'];
    }

    public const STATUS = ['recebida' => 'Recebida', 'em_atendimento' => 'Em atendimento', 'resolvida' => 'Resolvida'];

    public const PROXIMO = ['recebida' => 'em_atendimento', 'em_atendimento' => 'resolvida'];

    public function historico()
    {
        return DB::table('historico_status')->where('demanda_id', $this->id);
    }

    public function registrarEvento(string $status, bool $simular = false): void
    {
        DB::table('historico_status')->insert([
            'demanda_id' => $this->id, 'status' => $status,
            'notificacao_simulada' => $simular, 'ocorrido_em' => now(),
        ]);
    }
}
