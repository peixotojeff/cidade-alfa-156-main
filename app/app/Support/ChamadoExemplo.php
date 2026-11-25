<?php

namespace App\Support;

use App\Models\Demanda;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Chamado de exemplo: o fluxo completo que o Cidade Alfa 156+ propõe, do registro à
 * resolução dentro do prazo, com aviso ao cidadão em cada mudança de status.
 *
 * É a contraparte dos dados reais do SP156 (que não trazem início do atendimento nem
 * avisos). Fica fora dos indicadores e da fila da secretaria (origem = 'exemplo').
 */
class ChamadoExemplo
{
    public const PROTOCOLO = 'ALFA-0000000000000156';

    public const ORIGEM = 'exemplo';

    /** Devolve o exemplo; só grava no banco se ele estiver faltando ou tiver sido alterado. */
    public static function obter(): Demanda
    {
        $demanda = Demanda::where('protocolo', self::PROTOCOLO)->first();
        $intacto = $demanda && $demanda->origem === self::ORIGEM && $demanda->status === 'resolvida' && $demanda->avaliacao === 'excelente'
            && $demanda->historico()->count() === 3;

        return $intacto ? $demanda : self::garantir();
    }

    /** Cria ou restaura o exemplo com os dados fixos. Idempotente. */
    public static function garantir(): Demanda
    {
        return DB::transaction(function () {
            $aberto = Carbon::parse('2026-11-16 08:42:00');
            $atendimento = Carbon::parse('2026-11-17 10:15:00');
            $resolvido = Carbon::parse('2026-11-20 16:30:00');

            $demanda = Demanda::where('protocolo', self::PROTOCOLO)->first() ?? new Demanda(['protocolo' => self::PROTOCOLO]);
            $demanda->forceFill([
                'servico' => 'via',
                'descricao' => 'Buraco grande na faixa da direita, em frente ao número 1000. Os carros estão desviando para a outra faixa.',
                'latitude' => '-23.575501',
                'longitude' => '-46.640206',
                'logradouro' => 'Rua Vergueiro',
                'numero' => '1000',
                'distrito' => 'Liberdade',
                'subprefeitura' => 'Sé',
                'status' => 'resolvida',
                'prazo' => $aberto->copy()->addDays(config('cidade.servicos.via.dias')),
                'finalizada_em' => $resolvido,
                'avaliacao' => 'excelente',
                'avaliada_em' => Carbon::parse('2026-11-20 18:05:00'),
                'origem' => self::ORIGEM,
                'created_at' => $aberto,
                'updated_at' => $resolvido,
            ])->save();

            DB::table('historico_status')->where('demanda_id', $demanda->id)->delete();
            DB::table('historico_status')->insert([
                ['demanda_id' => $demanda->id, 'status' => 'recebida', 'notificacao_simulada' => false, 'ocorrido_em' => $aberto],
                ['demanda_id' => $demanda->id, 'status' => 'em_atendimento', 'notificacao_simulada' => true, 'ocorrido_em' => $atendimento],
                ['demanda_id' => $demanda->id, 'status' => 'resolvida', 'notificacao_simulada' => true, 'ocorrido_em' => $resolvido],
            ]);

            return $demanda;
        });
    }
}
