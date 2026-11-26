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
    | Automações e gestão
    |--------------------------------------------------------------------------
    */
    'bandejas_automaticas' => (bool) env('ALIMENTACAO_BANDEJAS_AUTO', true),
    'estimar_porcoes_ao_lancar' => (bool) env('ALIMENTACAO_ESTIMAR_PORCOES', true),
    'unidade_so_registra_fatos' => (bool) env('ALIMENTACAO_UNIDADE_FATOS', true),
    'alerta_horario_turma' => (bool) env('ALIMENTACAO_ALERTA_HORARIO', true),
    'porcoes_padrao' => (int) env('ALIMENTACAO_PORCOES_PADRAO', 100),
    'lanches_por_dia' => (int) env('ALIMENTACAO_LANCHES_POR_DIA', 2),
    'incluir_jantar' => (bool) env('ALIMENTACAO_INCLUIR_JANTAR', false),
    'slots_refeicao' => ['cafe', 'almoco', 'lanche', 'lanche_2'],
    'janelas' => [
        'cafe' => ['inicio' => '07:00', 'fim' => '09:00'],
        'almoco' => ['inicio' => '11:00', 'fim' => '13:30'],
        'lanche' => ['inicio' => '09:00', 'fim' => '10:30'],
        'lanche_2' => ['inicio' => '14:00', 'fim' => '16:00'],
        'jantar' => ['inicio' => '17:30', 'fim' => '19:30'],
    ],
    // Padrões PoC Laguna — substitua pelo RT real em Configurações → Módulos
    'rt_nome' => env('ALIMENTACAO_RT_NOME', 'Nutricionista responsável (PoC Laguna)'),
    'rt_registro' => env('ALIMENTACAO_RT_REGISTRO', 'CRN-10 0000'),
    'rt_cargo' => env('ALIMENTACAO_RT_CARGO', 'Nutricionista responsável técnico'),
    'rt_email' => env('ALIMENTACAO_RT_EMAIL', 'alimentacao@laguna.sc.gov.br'),

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
