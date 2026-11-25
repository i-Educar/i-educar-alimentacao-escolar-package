@extends('alimentacao::layout')

@section('titulo', 'Mapa de distribuição')
@section('migalha', 'Mapa')

@section('acoes-topo')
    <a class="botao claro" href="{{ url('/alimentacao-escolar') }}"><i class="fa fa-dashboard"></i> Painel</a>
@endsection

@section('alimentacao')
    <div class="cabeca-tela">
        <div class="identidade-tela">
            <span class="marca-tela"><i class="fa fa-map"></i></span>
            <div>
                <h1>Mapa de distribuição</h1>
                <p class="lead">Total de porções: {{ $mapa['total'] ?? 0 }}</p>
            </div>
        </div>
    </div>

    <div class="bloco">
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Escola | Refeição</th><th>Quantidade</th></tr></thead>
                <tbody>
                @foreach(($mapa['por_chave'] ?? []) as $chave => $q)
                    <tr><td>{{ $chave }}</td><td>{{ $q }}</td></tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection
