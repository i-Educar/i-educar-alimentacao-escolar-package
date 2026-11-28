@extends('alimentacao::layout')
@section('titulo', 'Histórico')
@section('migalha', 'Histórico')
@section('icone-tela', 'fa-history')
@section('titulo-tela', 'Histórico de alterações')
@section('lead')
    Auditoria de cardápios, restrições e demais alterações do módulo
@endsection
@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/cardapio') }}" title="Cardápio" aria-label="Cardápio"><i aria-hidden="true" class="fa fa-cutlery"></i></a>
@endsection

@section('conteudo')
    <div class="bloco">
        <h2><i class="fa fa-list"></i> Últimas alterações</h2>
        @if(empty($linhas))
            <p style="margin:0;color:var(--suave)">Nenhum evento ainda. Salve um cardápio ou restrição para gerar histórico (tabela <code>laguna.auditoria</code>).</p>
        @else
            <table>
                <thead><tr><th>Quando</th><th>Ação</th><th>Detalhe</th><th>Usuário</th></tr></thead>
                <tbody>
                    @foreach($linhas as $l)
                        <tr>
                            <td>{{ $l['quando'] }}</td>
                            <td><span class="badge ok">{{ $l['acao'] }}</span></td>
                            <td>{{ $l['detalhe'] }}</td>
                            <td>{{ $l['usuario'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
