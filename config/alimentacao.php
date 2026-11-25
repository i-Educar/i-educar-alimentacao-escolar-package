<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Portal público na home/login
    |--------------------------------------------------------------------------
    |
    | Sem rota pública de família ainda — manter aparecer_na_home=false até
    | existir URL externa segura (ex.: cardápio aberto).
    |
    */
    'public' => [
        'aparecer_na_home' => (bool) env('ALIMENTACAO_APARECER_NA_HOME', false),
        'titulo' => env('ALIMENTACAO_HOME_TITULO', 'Alimentação escolar'),
        'url' => env('ALIMENTACAO_HOME_URL'),
        'ordem' => (int) env('ALIMENTACAO_HOME_ORDEM', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | PoC operacional — comportamento do ensaio Laguna
    |--------------------------------------------------------------------------
    |
    | montar_cardapio       Habilita formulário/POST para persistir cardápio
    |                       em alimentacao.cardapio_dia. Quando false, os
    |                       formulários somem e POSTs são bloqueados.
    | escolas_do_nucleo     Carrega lista de escolas do select a partir de
    |                       pmieducar.escola (join cadastro.juridica.fantasia)
    |                       ao invés de aceitar texto livre.
    | mapa_por_matriculas   Usa count de pmieducar.matricula ativa por escola
    |                       como fator do mapa (representa população atendida);
    |                       sem isso, só consolida quantidades cadastradas.
    |
    */
    'montar_cardapio' => (bool) env('ALIMENTACAO_MONTAR_CARDAPIO', true),
    'escolas_do_nucleo' => (bool) env('ALIMENTACAO_ESCOLAS_NUCLEO', true),
    'mapa_por_matriculas' => (bool) env('ALIMENTACAO_MAPA_POR_MATRICULAS', true),
    'lote_somente_uteis' => (bool) env('ALIMENTACAO_LOTE_SOMENTE_UTEIS', true),
    'laudo_max_kb' => (int) env('ALIMENTACAO_LAUDO_MAX_KB', 5120),

    /*
    |--------------------------------------------------------------------------
    | PNAE (alimentação) — não confundir com PNATE (transporte)
    |--------------------------------------------------------------------------
    */
    'pnae' => [
        'meta_af_percentual' => (int) env('ALIMENTACAO_PNAE_META_AF', 30),
        'bloquear_erro' => (bool) env('ALIMENTACAO_PNAE_BLOQUEAR_ERRO', false),
    ],

];
