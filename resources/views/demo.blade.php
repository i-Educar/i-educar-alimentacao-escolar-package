@extends('alimentacao::layout')

@section('titulo', 'Demo PoC')
@section('migalha', 'Demo')

@section('icone-tela', 'fa-play')
@section('titulo-tela', 'Demo PoC — alimentação')
@section('lead')
    Executa <code>alimentacao:demo</code> (seed cardápio + restrição + JSON em storage)
@endsection
@section('acoes-topo')
    <form method="post" action="{{ url('/alimentacao-escolar/demo') }}">
    @csrf
    <button class="botao icone" type="submit" title="Rodar agora" aria-label="Rodar agora"><i aria-hidden="true" class="fa fa-play"></i></button>
    </form>
@endsection

@section('conteudo')

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
