<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Demanda extends Model
{
    protected $fillable = [
        'protocolo', 'servico', 'descricao', 'latitude', 'longitude', 'status', 'prazo',
        'logradouro', 'numero', 'distrito', 'subprefeitura', 'origem', 'finalizada_em',
    ];

    protected function casts(): array
    {
        return ['prazo' => 'datetime', 'finalizada_em' => 'datetime', 'avaliada_em' => 'datetime'];
    }

    public const STATUS = ['recebida' => 'Recebida', 'em_atendimento' => 'Em atendimento', 'resolvida' => 'Resolvida'];

    public const PROXIMO = ['recebida' => 'em_atendimento', 'em_atendimento' => 'resolvida'];

    /** Ícone e tom visual de cada status (ver DESIGN.md › Status do chamado). */
    public const VISUAL = [
        'recebida' => ['icone' => 'entrada', 'tom' => 'sky', 'resumo' => 'A secretaria recebeu e vai avaliar.'],
        'em_atendimento' => ['icone' => 'ferramenta', 'tom' => 'berry', 'resumo' => 'Uma equipe está cuidando do problema.'],
        'resolvida' => ['icone' => 'check', 'tom' => 'leaf', 'resumo' => 'O problema foi resolvido.'],
    ];

    /** Situação do chamado em relação ao prazo (SLA). */
    public const PRAZO = [
        'no_prazo' => ['rotulo' => 'No prazo', 'tom' => 'sky', 'icone' => 'relogio'],
        'atrasado' => ['rotulo' => 'Atrasado', 'tom' => 'coral', 'icone' => 'alerta'],
        'resolvido_no_prazo' => ['rotulo' => 'Resolvido no prazo', 'tom' => 'leaf', 'icone' => 'check'],
        'resolvido_fora_do_prazo' => ['rotulo' => 'Resolvido fora do prazo', 'tom' => 'sun', 'icone' => 'relogio'],
    ];

    /** Avaliação do cidadão após a resolução (ver DESIGN.md › Avaliação). */
    public const AVALIACOES = [
        'excelente' => ['rotulo' => 'Excelente', 'icone' => 'rosto-feliz', 'tom' => 'leaf'],
        'bom' => ['rotulo' => 'Bom', 'icone' => 'rosto-ok', 'tom' => 'sky'],
        'pessimo' => ['rotulo' => 'Péssimo', 'icone' => 'rosto-triste', 'tom' => 'coral'],
    ];

    public function podeSerAvaliada(): bool
    {
        return $this->status === 'resolvida' && $this->avaliacao === null;
    }

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

    public function situacaoPrazo(?Carbon $agora = null): string
    {
        if ($this->status === 'resolvida') {
            return ($this->finalizada_em ?? $this->updated_at)->lte($this->prazo) ? 'resolvido_no_prazo' : 'resolvido_fora_do_prazo';
        }

        return ($agora ?? now())->gt($this->prazo) ? 'atrasado' : 'no_prazo';
    }

    /** Dias corridos entre a abertura e a resolução (null se ainda aberto). */
    public function diasAteResolver(): ?int
    {
        if ($this->status !== 'resolvida') {
            return null;
        }

        return (int) $this->created_at->copy()->startOfDay()->diffInDays(($this->finalizada_em ?? $this->updated_at)->copy()->startOfDay());
    }

    public function endereco(): ?string
    {
        $rua = trim(implode(', ', array_filter([$this->logradouro, $this->numero])));
        $regiao = trim(implode(' · ', array_filter([$this->distrito, $this->subprefeitura !== $this->distrito ? $this->subprefeitura : null])));

        return trim(implode(' — ', array_filter([$rua, $regiao]))) ?: null;
    }

    public function veioDoSp156(): bool
    {
        return $this->origem === 'sp156';
    }

    public function ehExemplo(): bool
    {
        return $this->origem === \App\Support\ChamadoExemplo::ORIGEM;
    }

    /** Dias de folga entre a resolução e o prazo (positivo = resolvido antes). */
    public function folgaDoPrazo(): ?int
    {
        if ($this->status !== 'resolvida') {
            return null;
        }

        $fim = ($this->finalizada_em ?? $this->updated_at)->copy()->startOfDay();

        return (int) $fim->diffInDays($this->prazo->copy()->startOfDay(), false);
    }

    /** Chamados reais (do app e do SP156), sem o exemplo de demonstração. */
    public function scopeReais($query)
    {
        return $query->where('origem', '!=', \App\Support\ChamadoExemplo::ORIGEM);
    }
}
