@extends('alimentacao::layout')

@section('titulo', 'Início — Alimentação')
@section('migalha', 'Início')
@section('icone-tela', 'fa-leaf')
@section('titulo-tela', 'Merenda escolar da rede')
@section('lead', 'Monte o cardápio do dia, registre restrições alimentares e acompanhe a distribuição nas escolas.')

@section('acoes-topo')
    <a class="botao" href="{{ url('/alimentacao-escolar/cardapio') }}"><i class="fa fa-cutlery"></i> Montar cardápio</a>
    <a class="botao claro" href="{{ url('/alimentacao-escolar/mapa') }}"><i class="fa fa-map-o"></i> Mapa</a>
@endsection

@section('conteudo')
    @unless($temSchema ?? true)
        <p class="flash alerta">Schema <code>alimentacao.*</code> ainda não migrado. Rode <code>php artisan migrate</code>.</p>
    @endunless

    @include('buriti-ui::painel-corpo', [
        'kpis' => $kpis ?? [],
        'atalhos' => $atalhos ?? [],
        'destaques' => $destaques ?? [],
        'analise' => $analise ?? [],
        'analiseTitulo' => $analiseTitulo ?? 'Análise',
        'resumo' => $resumo ?? null,
        'fluxoTitulo' => $fluxoTitulo ?? 'Operação da merenda',
    ])
@endsection
