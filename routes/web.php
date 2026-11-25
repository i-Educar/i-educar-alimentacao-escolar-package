<?php

use iEducar\Packages\BuritiAlimentacaoEscolar\Http\Controllers\CardapioController;
use Illuminate\Support\Facades\Route;

Route::prefix('alimentacao-escolar')->group(function (): void {
    Route::get('/', [CardapioController::class, 'index']);
    Route::get('/cardapio', [CardapioController::class, 'cardapio']);
});
