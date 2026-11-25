<?php

namespace App\Support;

use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * Amostra dos dados abertos do SP156 (Prefeitura de São Paulo, licença CC0).
 *
 * Fonte: https://dados.prefeitura.sp.gov.br/dataset/dados-do-sp156 — 1º a 3º trimestre de 2026.
 * O CSV em database/seeders/data/sp156-amostra.csv é gerado por scripts/amostra_sp156.py.
 *
 * Os dados abertos não trazem descrição do cidadão nem a data de início do atendimento:
 * a descrição usa o nome do serviço no SP156 e a etapa "em atendimento" fica sem data.
 */
class Sp156Amostra
{
    public const ORIGEM = 'sp156';

    public static function caminho(): string
    {
        return database_path('seeders/data/sp156-amostra.csv');
    }

    /**
     * @return list<array{demanda: array<string, mixed>, eventos: list<array<string, mixed>>}>
     */
    public static function registros(): array
    {
        $servicos = config('cidade.servicos');
        $arquivo = fopen(self::caminho(), 'r') ?: throw new RuntimeException('Amostra do SP156 não encontrada.');
        $cabecalho = fgetcsv($arquivo, escape: '');
        $registros = [];

        while (($linha = fgetcsv($arquivo, escape: '')) !== false) {
            $l = array_combine($cabecalho, $linha);
            $servico = $servicos[$l['servico']] ?? throw new RuntimeException("Serviço desconhecido na amostra: {$l['servico']}");
            $aberta = Carbon::parse($l['aberta_em'])->startOfDay();
            $finalizada = $l['finalizada_em'] !== '' ? Carbon::parse($l['finalizada_em'])->startOfDay() : null;

            $eventos = [['status' => 'recebida', 'notificacao_simulada' => false, 'ocorrido_em' => $aberta]];
            if ($l['status'] === 'resolvida' && $finalizada) {
                $eventos[] = ['status' => 'resolvida', 'notificacao_simulada' => false, 'ocorrido_em' => $finalizada];
            }

            $registros[] = [
                'demanda' => [
                    // Protocolo estável: o mesmo registro gera o mesmo protocolo no SQLite e no Supabase.
                    'protocolo' => 'ALFA-'.strtoupper(substr(sha1('sp156|'.implode('|', $linha)), 0, 16)),
                    'servico' => $l['servico'],
                    'descricao' => $l['servico_sp156'],
                    'latitude' => $l['latitude'],
                    'longitude' => $l['longitude'],
                    'status' => $l['status'],
                    'prazo' => $aberta->copy()->addDays($servico['dias']),
                    'created_at' => $aberta,
                    'updated_at' => $finalizada ?? $aberta,
                    'logradouro' => $l['logradouro'] ?: null,
                    'numero' => $l['numero'] ?: null,
                    'distrito' => $l['distrito'] ?: null,
                    'subprefeitura' => $l['subprefeitura'] ?: null,
                    'origem' => self::ORIGEM,
                    'finalizada_em' => $finalizada,
                ],
                'eventos' => $eventos,
            ];
        }
        fclose($arquivo);

        return $registros;
    }
}
