<?php

use iEducar\Packages\BuritiAlimentacaoEscolar\Http\Controllers\CardapioController;
use Illuminate\Support\Facades\Route;

Route::prefix('alimentacao-escolar')->group(function (): void {
    Route::get('/', [CardapioController::class, 'index']);
    Route::get('/cardapio', [CardapioController::class, 'cardapio']);
    Route::post('/cardapio', [CardapioController::class, 'salvarCardapio']);
    Route::get('/cardapio-lote', [CardapioController::class, 'cardapioLote']);
    Route::post('/cardapio-lote', [CardapioController::class, 'salvarCardapioLote']);
    Route::get('/itens', [CardapioController::class, 'itens']);
    Route::post('/itens', [CardapioController::class, 'salvarItem']);
    Route::get('/restricoes', [CardapioController::class, 'restricoes']);
    Route::post('/restricoes', [CardapioController::class, 'salvarRestricao']);
    Route::get('/restricoes/{id}/laudo', [CardapioController::class, 'baixarLaudo'])->whereNumber('id');
    Route::get('/historico', [CardapioController::class, 'historico']);
    Route::get('/mapa', [CardapioController::class, 'mapa']);
    Route::get('/demo', [CardapioController::class, 'demo']);
    Route::post('/demo', [CardapioController::class, 'rodarDemo']);
});
