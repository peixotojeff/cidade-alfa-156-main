<?php

namespace Database\Seeders;

use App\Support\Sp156Amostra;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Carrega a amostra do SP156 (600 chamados reais, CC0). Idempotente: apaga só os
 * registros com origem 'sp156' e recria. Chamados abertos no app não são tocados.
 *
 *   php artisan db:seed --class=Sp156Seeder
 */
class Sp156Seeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $ids = DB::table('demandas')->where('origem', Sp156Amostra::ORIGEM)->pluck('id');
            DB::table('historico_status')->whereIn('demanda_id', $ids)->delete();
            DB::table('demandas')->whereIn('id', $ids)->delete();

            foreach (Sp156Amostra::registros() as $registro) {
                $id = DB::table('demandas')->insertGetId($registro['demanda']);
                DB::table('historico_status')->insert(array_map(fn ($e) => $e + ['demanda_id' => $id], $registro['eventos']));
            }
        });
    }
}
