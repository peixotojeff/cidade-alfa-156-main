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
