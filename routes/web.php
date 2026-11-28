<?php

use iEducar\Packages\BuritiAlimentacaoEscolar\Http\Controllers\CardapioController;
use Illuminate\Support\Facades\Route;

Route::prefix('alimentacao-escolar')->group(function (): void {
    Route::get('/', [CardapioController::class, 'index']);
    Route::get('/cardapio', [CardapioController::class, 'cardapio']);
    Route::post('/cardapio', [CardapioController::class, 'salvarCardapio']);
    Route::get('/cardapio/imprimir', [CardapioController::class, 'cardapioImpressao']);
    Route::post('/cardapio/imprimir', [CardapioController::class, 'cardapioImpressao']);
    Route::get('/cardapio/cronograma-pdf', [CardapioController::class, 'cronogramaPdf']);
    Route::post('/cardapio/cronograma-pdf', [CardapioController::class, 'cronogramaPdf']);
    Route::get('/cardapio-lote', [CardapioController::class, 'cardapioLote']);
    Route::post('/cardapio-lote', [CardapioController::class, 'salvarCardapioLote']);
    Route::get('/itens', [CardapioController::class, 'itens']);
    Route::post('/itens', [CardapioController::class, 'salvarItem']);
    Route::get('/perfil', [CardapioController::class, 'perfil']);
    Route::post('/perfil', [CardapioController::class, 'salvarPerfil']);
    Route::get('/balanco', [CardapioController::class, 'balanco']);
    Route::get('/sobras', [CardapioController::class, 'sobras']);
    Route::post('/sobras', [CardapioController::class, 'salvarSobras']);
    Route::get('/restricoes', [CardapioController::class, 'restricoes']);

    Route::post('/restricoes', [CardapioController::class, 'salvarRestricao']);
    Route::get('/restricoes/{id}/laudo', [CardapioController::class, 'baixarLaudo'])->whereNumber('id');
    Route::get('/historico', [CardapioController::class, 'historico']);
    Route::get('/mapa', [CardapioController::class, 'mapa']);
    Route::get('/api/turmas', [CardapioController::class, 'turmasJson']);
    Route::get('/configuracoes', [CardapioController::class, 'configuracoes']);
    Route::post('/configuracoes', [CardapioController::class, 'salvarConfiguracoes']);
    Route::get('/demo', [CardapioController::class, 'demo']);
    Route::post('/demo', [CardapioController::class, 'rodarDemo']);
    
    // Fichas técnicas (5.2.7, 5.2.8, 5.2.9)
    Route::get('/fichas-tecnicas', [CardapioController::class, 'fichasTecnicas']);
    Route::get('/fichas-tecnicas/nova', [CardapioController::class, 'fichaTecnicaForm']);
    Route::get('/fichas-tecnicas/{id}/editar', [CardapioController::class, 'fichaTecnicaForm'])->whereNumber('id');
    Route::post('/fichas-tecnicas', [CardapioController::class, 'salvarFichaTecnica']);
    Route::post('/fichas-tecnicas/copiar', [CardapioController::class, 'copiarFichaTecnica']);
    Route::delete('/fichas-tecnicas/{id}', [CardapioController::class, 'excluirFichaTecnica'])->whereNumber('id');
    Route::post('/fichas-tecnicas/{id}/excluir', [CardapioController::class, 'excluirFichaTecnica'])->whereNumber('id');
    Route::post('/fichas-tecnicas/exportar', [CardapioController::class, 'exportarFichasTecnicas']);
});
