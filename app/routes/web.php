<?php

use App\Http\Controllers\DemandaController;
use Illuminate\Support\Facades\Route;

Route::get('/', [DemandaController::class, 'inicio'])->name('inicio');
Route::post('/chamados', [DemandaController::class, 'abrir'])->name('abrir');
Route::post('/consultar', [DemandaController::class, 'consultar'])->name('consultar');
Route::get('/acompanhar/{protocolo}', [DemandaController::class, 'acompanhar'])->name('acompanhar');
Route::middleware('throttle:60,1')->group(function () {
    Route::get('/secretaria', [DemandaController::class, 'secretaria'])->name('secretaria');
    Route::post('/secretaria/{protocolo}/status', [DemandaController::class, 'atualizar'])->name('atualizar');
});
