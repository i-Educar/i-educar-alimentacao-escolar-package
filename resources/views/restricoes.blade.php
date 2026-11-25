@extends('alimentacao::layout')

@section('titulo', 'Restrições alimentares')
@section('migalha', 'Restrições')

@section('acoes-topo')
    <a class="botao" href="{{ url('/alimentacao-escolar/cardapio') }}"><i class="fa fa-cutlery"></i> Cardápio</a>
@endsection

@section('alimentacao')
    <div class="cabeca-tela">
        <div class="identidade-tela">
            <span class="marca-tela"><i class="fa fa-exclamation-triangle"></i></span>
            <div>
                <h1>Restrições alimentares</h1>
                <p class="lead">Vínculo lógico ao aluno do núcleo (`ref_cod_aluno`)</p>
            </div>
        </div>
    </div>

    <div class="bloco">
        <div class="tabela-wrap">
            <table>
                <thead><tr><th>Aluno (cod)</th><th>Restrição</th><th>Atualizado</th></tr></thead>
                <tbody>
                @forelse($linhas as $l)
                    <tr>
                        <td>{{ $l->ref_cod_aluno ?? $l->aluno_id ?? '—' }}</td>
                        <td>{{ $l->restricao }}</td>
                        <td>{{ $l->updated_at ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Nenhuma restrição. Rode a demo PoC.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
