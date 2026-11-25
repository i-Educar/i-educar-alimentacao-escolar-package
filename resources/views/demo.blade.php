@extends('alimentacao::layout')

@section('titulo', 'Demo PoC')
@section('migalha', 'Demo')

@section('alimentacao')
    <div class="cabeca-tela">
        <div class="identidade-tela">
            <span class="marca-tela"><i class="fa fa-play"></i></span>
            <div>
                <h1>Demo PoC — alimentação</h1>
                <p class="lead">Executa <code>alimentacao:demo</code> (seed cardápio + restrição + JSON em storage)</p>
            </div>
        </div>
        <div class="acoes-tela">
            <form method="post" action="{{ url('/alimentacao-escolar/demo') }}">
                @csrf
                <button class="botao" type="submit"><i class="fa fa-play"></i> Rodar agora</button>
            </form>
        </div>
    </div>

    <div class="bloco">
        <h2>O que a demo faz</h2>
        <ul>
            <li>Grava cardápio do dia em <code>alimentacao.cardapio_dia</code></li>
            <li>Grava restrição exemplo (leite) em <code>alimentacao.restricao_aluno</code></li>
            <li>Gera JSON em <code>storage/app/alimentacao-onda4/</code></li>
        </ul>
        <p><a class="botao claro" href="{{ url('/alimentacao-escolar/cardapio') }}"><i class="fa fa-cutlery"></i> Ir ao cardápio</a></p>
    </div>
@endsection
