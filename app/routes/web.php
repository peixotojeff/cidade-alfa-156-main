<?php

use App\Http\Controllers\DemandaController;
use Illuminate\Support\Facades\Route;

// Uma página por objetivo do cidadão.
Route::get('/', [DemandaController::class, 'inicio'])->name('inicio');
Route::get('/chamados/novo', [DemandaController::class, 'novo'])->name('novo');
Route::post('/chamados', [DemandaController::class, 'abrir'])->name('abrir');
Route::get('/acompanhar', [DemandaController::class, 'consulta'])->name('consulta');
Route::post('/consultar', [DemandaController::class, 'consultar'])->name('consultar');
Route::get('/acompanhar/{protocolo}', [DemandaController::class, 'acompanhar'])->name('acompanhar');
Route::post('/acompanhar/{protocolo}/avaliacao', [DemandaController::class, 'avaliar'])->middleware('throttle:20,1')->name('avaliar');
Route::get('/indicadores', [DemandaController::class, 'indicadores'])->name('indicadores');
Route::get('/analise', [DemandaController::class, 'analise'])->name('analise');

Route::middleware('throttle:60,1')->group(function () {
    Route::get('/secretaria', [DemandaController::class, 'secretaria'])->name('secretaria');
    Route::post('/secretaria/{protocolo}/status', [DemandaController::class, 'atualizar'])->name('atualizar');
    Route::post('/secretaria/amostra-sp156', [DemandaController::class, 'importarSp156'])->name('importar-sp156');
});
