@extends('alimentacao::layout')

@section('titulo', 'Cardápio do dia')
@section('migalha', 'Cardápio')

@section('acoes-topo')
    <a class="botao claro" href="{{ url('/alimentacao-escolar') }}"><i class="fa fa-dashboard"></i> Painel</a>
    <a class="botao" href="{{ url('/alimentacao-escolar/demo') }}"><i class="fa fa-refresh"></i> Seed demo</a>
@endsection

@section('alimentacao')
    <div class="cabeca-tela">
        <div class="identidade-tela">
            <span class="marca-tela"><i class="fa fa-cutlery"></i></span>
            <div>
                <h1>Cardápio do dia</h1>
                <p class="lead">Fonte: {{ $fonte }} · {{ $cardapio['escola'] ?? $escola }} · {{ $cardapio['total_itens'] ?? 0 }} itens</p>
            </div>
        </div>
        <div class="acoes-tela">
            <a class="botao claro" href="{{ url('/alimentacao-escolar/restricoes') }}"><i class="fa fa-exclamation-triangle"></i> Ver restrições</a>
        </div>
    </div>

    <form class="filtro" method="get">
        <label>Data <input type="date" name="data" value="{{ $data }}"></label>
        <label>Escola <input name="escola" value="{{ $escola }}"></label>
        <button class="botao" type="submit"><i class="fa fa-search"></i> Filtrar</button>
    </form>

    @foreach(($cardapio['refeicoes'] ?? []) as $ref)
        <div class="bloco">
            <h2><i class="fa fa-coffee"></i> {{ ucfirst($ref['refeicao']) }}</h2>
            <ul>
                @foreach(($ref['itens'] ?? []) as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        </div>
    @endforeach

    @if(!empty($alertas))
        <div class="bloco" style="border-top-color:#b86e00">
            <h2>Conflitos com restrições</h2>
            <div class="tabela-wrap">
                <table>
                    <thead><tr><th>Aluno</th><th>Restrição</th></tr></thead>
                    <tbody>
                    @foreach($alertas as $a)
                        <tr><td>{{ $a['aluno_id'] }}</td><td>{{ $a['restricao'] }}</td></tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
@endsection
