<?php

namespace App\Support;

use App\Models\Demanda;
use Illuminate\Support\Collection;

/**
 * Indicadores de SLA exibidos ao cidadão e à secretaria — os mesmos da camada Ouro da
 * arquitetura do projeto: resolutividade no prazo e tempo de atendimento.
 *
 * Calculados em PHP para funcionar igual no SQLite (demo) e no Postgres (produção).
 * O volume do protótipo é de centenas de registros; para milhões, mover para SQL/Gold.
 */
class Indicadores
{
    /** @return array{geral: array, servicos: array<string, array>} */
    public static function calcular(?Collection $demandas = null): array
    {
        $demandas ??= Demanda::query()->get(['id', 'servico', 'status', 'prazo', 'created_at', 'updated_at', 'finalizada_em']);
        $agora = now();
        $porServico = [];
        foreach (config('cidade.servicos') as $codigo => $servico) {
            $porServico[$codigo] = self::resumo($demandas->where('servico', $codigo), $agora) + ['nome' => $servico['nome'], 'icone' => $servico['icone'], 'dias' => $servico['dias']];
        }

        return ['geral' => self::resumo($demandas, $agora), 'servicos' => $porServico];
    }

    private static function resumo(Collection $lote, $agora): array
    {
        $resolvidas = $lote->where('status', 'resolvida');
        $abertas = $lote->where('status', '!=', 'resolvida');
        $noPrazo = $resolvidas->filter(fn (Demanda $d) => $d->situacaoPrazo($agora) === 'resolvido_no_prazo')->count();
        $dias = $resolvidas->map(fn (Demanda $d) => $d->diasAteResolver())->sort()->values();

        return [
            'total' => $lote->count(),
            'resolvidas' => $resolvidas->count(),
            'abertas' => $abertas->count(),
            'atrasadas' => $abertas->filter(fn (Demanda $d) => $d->situacaoPrazo($agora) === 'atrasado')->count(),
            'resolvidas_no_prazo' => $noPrazo,
            // Resolutividade no prazo: % das resolvidas que terminaram dentro do SLA.
            'pct_no_prazo' => $resolvidas->count() ? (int) round($noPrazo / $resolvidas->count() * 100) : null,
            'mediana_dias' => $dias->count() ? (int) round($dias->median()) : null,
        ];
    }
}
