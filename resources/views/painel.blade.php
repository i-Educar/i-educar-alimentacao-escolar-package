@extends('alimentacao::layout')

@section('titulo', 'Painel — Alimentação')
@section('migalha', 'Painel')

@section('acoes-topo')
    <a class="botao" href="{{ url('/alimentacao-escolar/cardapio') }}"><i class="fa fa-cutlery"></i> Cardápio</a>
    <a class="botao claro" href="{{ url('/alimentacao-escolar/demo') }}"><i class="fa fa-play"></i> Demo</a>
@endsection

@section('alimentacao')
    <div class="cabeca-tela">
        <div class="identidade-tela">
            <span class="marca-tela"><i class="fa fa-dashboard"></i></span>
            <div>
                <h1>Painel da alimentação</h1>
                <p class="lead">TR alimentação · cor-base verde PNAE</p>
            </div>
        </div>
        <div class="acoes-tela">
            <a class="botao" href="{{ url('/alimentacao-escolar/cardapio') }}"><i class="fa fa-cutlery"></i> Abrir cardápio</a>
            <a class="botao claro" href="{{ url('/alimentacao-escolar/restricoes') }}"><i class="fa fa-exclamation-triangle"></i> Restrições</a>
            <a class="botao claro" href="{{ url('/alimentacao-escolar/mapa') }}"><i class="fa fa-map"></i> Mapa</a>
        </div>
    </div>

    @unless($temSchema)
        <p class="flash alerta">Schema <code>alimentacao.*</code> ainda não migrado. Rode <code>php artisan migrate</code> com o pacote liberado no PackageTenant.</p>
    @endunless

    <div class="numeros">
        <article><span>Cardápios</span><strong>{{ $totalCardapios }}</strong></article>
        <article><span>Restrições</span><strong>{{ $totalRestricoes }}</strong></article>
        <article><span>Schema</span><strong>{{ $temSchema ? 'OK' : '—' }}</strong></article>
    </div>

    <div class="bloco">
        <h2>Atalhos</h2>
        <ul class="atalhos">
            <li><a href="{{ url('/alimentacao-escolar/cardapio') }}"><i class="fa fa-cutlery"></i> Cardápio do dia</a></li>
            <li><a href="{{ url('/alimentacao-escolar/restricoes') }}"><i class="fa fa-exclamation-triangle"></i> Restrições</a></li>
            <li><a href="{{ url('/alimentacao-escolar/mapa') }}"><i class="fa fa-map"></i> Mapa distribuição</a></li>
            <li><a href="{{ url('/alimentacao-escolar/demo') }}"><i class="fa fa-play"></i> Rodar demo PoC</a></li>
            <li><a href="{{ url('/transporte-escolar') }}"><i class="fa fa-bus"></i> Ir ao transporte</a></li>
        </ul>
    </div>
@endsection
