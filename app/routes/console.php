<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('cidade:migrate-demo', function () {
    $path = database_path('demo.sqlite');
    $connection = config('database.connections.sqlite');

    if (! config('cidade.demo') || config('database.default') !== 'sqlite'
        || ($connection['driver'] ?? null) !== 'sqlite' || ! empty($connection['url'])
        || ($connection['database'] ?? null) !== $path || is_link($path)
        || (file_exists($path) && ! is_file($path))) {
        $this->error('Migração bloqueada: o destino deve ser o SQLite local da demonstração.');

        return 1;
    }

    if (! file_exists($path) && ! touch($path)) {
        $this->error('Não foi possível criar o banco local da demonstração.');

        return 1;
    }

    return $this->call('migrate', ['--database' => 'sqlite', '--force' => true, '--no-interaction' => true]);
})->purpose('Migra exclusivamente o SQLite local da demonstração');

Artisan::command('cidade:sp156-sql {arquivo : Caminho do .sql a gerar}', function (string $arquivo) {
    // Gera SQL (Postgres) equivalente ao Sp156Seeder, para aplicar no Supabase pelo SQL Editor ou MCP.
    $q = fn ($v) => $v === null ? 'null' : "'".str_replace("'", "''", $v instanceof \DateTimeInterface ? $v->format('Y-m-d H:i:s') : (string) $v)."'";
    $sql = ["-- Amostra SP156 (dados abertos da Prefeitura de São Paulo, CC0). Idempotente: substitui só origem = 'sp156'.", 'begin;',
        "delete from historico_status where demanda_id in (select id from demandas where origem = 'sp156');",
        "delete from demandas where origem = 'sp156';"];
    foreach (\App\Support\Sp156Amostra::registros() as $r) {
        $d = $r['demanda'];
        $sql[] = 'insert into demandas ('.implode(', ', array_keys($d)).') values ('.implode(', ', array_map($q, $d)).');';
        foreach ($r['eventos'] as $e) {
            $sql[] = sprintf("insert into historico_status (demanda_id, status, notificacao_simulada, ocorrido_em) select id, %s, false, %s from demandas where protocolo = %s;",
                $q($e['status']), $q($e['ocorrido_em']), $q($d['protocolo']));
        }
    }
    $sql[] = 'commit;';
    file_put_contents($arquivo, implode("\n", $sql)."\n");
    $this->info(count(\App\Support\Sp156Amostra::registros()).' chamados exportados para '.$arquivo);
})->purpose('Exporta a amostra do SP156 como SQL para o Postgres (Supabase)');
