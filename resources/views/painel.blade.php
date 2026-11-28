@extends('alimentacao::layout')

@section('titulo', 'Início — Alimentação')
@section('migalha', 'Início')
@section('icone-tela', 'fa-leaf')
@section('titulo-tela', 'Merenda escolar da rede')
@section('lead', 'Monte o cardápio do dia, registre restrições alimentares e acompanhe a distribuição nas escolas.')

@section('acoes-topo')
    <a class="botao icone" href="{{ url('/alimentacao-escolar/cardapio') }}" title="Montar cardápio" aria-label="Montar cardápio"><i aria-hidden="true" class="fa fa-cutlery"></i></a>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/mapa') }}" title="Mapa" aria-label="Mapa"><i aria-hidden="true" class="fa fa-map-o"></i></a>
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
