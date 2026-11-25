@extends('alimentacao::layout')

@section('titulo', 'Itens / PNAE')
@section('migalha', 'Itens')
@section('icone-tela', 'fa-list')
@section('titulo-tela', 'Catálogo de itens')
@section('lead', 'Configure alimentos usados nos cardápios e classifique-os para as regras PNAE')

@section('acoes-topo')
    <a class="botao claro" href="{{ url('/alimentacao-escolar/cardapio') }}"><i class="fa fa-cutlery"></i> Cardápio</a>
@endsection

@section('conteudo')
@if($podeMontar)
<div class="bloco">
    <h2><i class="fa fa-plus"></i> Novo / atualizar item</h2>
    <form class="form-grid" method="post" action="{{ url('/alimentacao-escolar/itens') }}">
        @csrf
        <input type="hidden" name="id" value="0">
        <label>Nome <input name="nome" required placeholder="ex.: feijão"></label>
        <label>Categoria
            <select name="categoria">
                <option value="in_natura">In natura</option>
                <option value="minimamente">Minimamente processado</option>
                <option value="processado">Processado</option>
                <option value="ultraprocessado">Ultraprocessado</option>
            </select>
        </label>
        <label>Grupo PNAE
            <select name="grupo_pnae">
                <option value="cereal">Cereal</option>
                <option value="leguminosa">Leguminosa</option>
                <option value="hortalica">Hortaliça</option>
                <option value="fruta">Fruta</option>
                <option value="proteina">Proteína</option>
                <option value="laticinio">Laticínio</option>
                <option value="outro">Outro</option>
            </select>
        </label>
        <label>Unidade <input name="unidade" value="porção"></label>
        <label><span>Agricultura familiar</span>
            <select name="origem_af"><option value="0">Não</option><option value="1">Sim</option></select>
        </label>
        <label><span>Ativo</span>
            <select name="ativo"><option value="1">Sim</option><option value="0">Não</option></select>
        </label>
        <div class="acoes"><button class="botao" type="submit"><i class="fa fa-save"></i> Salvar item</button></div>
    </form>
</div>
@endif

<div class="bloco">
    <h2><i class="fa fa-table"></i> Itens cadastrados ({{ count($itens) }})</h2>
    <div style="overflow:auto">
        <table>
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Categoria</th>
                    <th>Grupo PNAE</th>
                    <th>AF</th>
                    <th>Ativo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($itens as $item)
                    <tr>
                        <td>{{ $item['nome'] }}</td>
                        <td>{{ $item['categoria'] }}</td>
                        <td>{{ $item['grupo_pnae'] }}</td>
                        <td>@if($item['origem_af']) <span class="badge ok">AF</span> @else — @endif</td>
                        <td>@if($item['ativo']) <span class="badge ok">sim</span> @else <span class="badge fail">não</span> @endif</td>
                    </tr>
                @empty
                    <tr><td colspan="5">Nenhum item. Rode a migration ou cadastre acima.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
