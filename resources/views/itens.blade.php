@extends('alimentacao::layout')

@section('titulo', 'Itens / PNAE')
@section('migalha', 'Itens')
@section('icone-tela', 'fa-list')
@section('titulo-tela', 'Catálogo de itens')
@section('lead', 'Peso, kcal e custo alimentam o balanço e a previsão de compras')

@section('acoes-topo')
    @if(!empty($podeEditar))
        <button type="button" class="botao primaria icone" data-buriti-modal="modal-novo-item" title="Novo item" aria-label="Novo item"><i aria-hidden="true" class="fa fa-plus"></i></button>
    @else
        <button type="button" class="botao buriti-acao-bloqueada icone" disabled title="Sem permissão para editar o catálogo" aria-label="Novo item"><i aria-hidden="true" class="fa fa-lock"></i></button>
    @endif
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/cardapio') }}" title="Cardápio" aria-label="Cardápio"><i aria-hidden="true" class="fa fa-cutlery"></i></a>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/balanco') }}" title="Balanço" aria-label="Balanço"><i aria-hidden="true" class="fa fa-balance-scale"></i></a>
@endsection

@section('foco')
@php
    $ativos = collect($itens ?? [])->where('ativo', true)->count();
    $faltando = (int) ($semNutricao ?? 0);
@endphp
<section class="buriti-foco">
    <p class="buriti-foco-kicker">Catálogo PNAE</p>
    <h2 class="buriti-foco-titulo">
        @if($faltando > 0)
            {{ $faltando }} item{{ $faltando === 1 ? '' : 's' }} sem peso, kcal ou custo
        @else
            {{ $ativos }} item{{ $ativos === 1 ? '' : 's' }} ativos com dados para o balanço
        @endif
    </h2>
    <p class="buriti-foco-texto">
        @if(!empty($podeEditar))
            Edite pela linha · preencha peso (g), kcal e custo unitário antes de fechar o cardápio.
        @else
            Visualização — peça cadastra no processo «Itens / catálogo PNAE» para editar.
        @endif
    </p>
</section>
@endsection

@section('conteudo')
<style>
    .itens-tabela { width: 100%; border-collapse: collapse; min-width: 720px; }
    .itens-tabela th, .itens-tabela td {
        text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--linha);
        vertical-align: middle; white-space: nowrap;
    }
    .itens-tabela th { background: var(--clara); color: var(--cor); font-size: .78rem; }
    .itens-tabela td.nome { white-space: normal; max-width: 180px; font-weight: 700; }
    .itens-tabela .falta { color: var(--ambar); font-weight: 700; }
    .itens-tabela .acoes-linha { display: inline-flex; gap: 6px; flex-wrap: wrap; }
    .itens-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; max-width: 100%; }
</style>

<div class="bloco">
    <h2><i class="fa fa-table"></i> Itens cadastrados</h2>
    <div class="itens-wrap">
        <table class="itens-tabela">
            <thead>
                <tr>
                    <th>Nome</th>
                    <th>Grupo</th>
                    <th>Refeições</th>
                    <th>Peso (g)</th>
                    <th>kcal</th>
                    <th>Custo</th>
                    <th>AF</th>
                    <th>Status</th>
                    @if(!empty($podeEditar))
                        <th></th>
                    @endif
                </tr>
            </thead>
            <tbody>
                @forelse($itens as $item)
                    <tr>
                        <td class="nome">
                            {{ $item['nome'] }}
                            <div style="font-size:.72rem;font-weight:500;color:var(--suave)">{{ $item['categoria'] }}</div>
                        </td>
                        <td>{{ $item['grupo_pnae'] }}</td>
                        <td style="white-space:normal;max-width:140px;font-size:.78rem">
                            {{ collect($item['refeicoes'] ?? [])->map(fn ($r) => match($r) {
                                'cafe' => 'Café', 'almoco' => 'Almoço', 'lanche' => 'Lanche', 'jantar' => 'Jantar',
                                default => $r,
                            })->implode(', ') ?: '—' }}
                        </td>
                        <td class="{{ $item['peso_porcao_g'] === null ? 'falta' : '' }}">
                            {{ $item['peso_porcao_g'] !== null ? number_format($item['peso_porcao_g'], 0, ',', '.') : 'falta' }}
                        </td>
                        <td class="{{ $item['kcal_porcao'] === null ? 'falta' : '' }}">
                            {{ $item['kcal_porcao'] !== null ? number_format($item['kcal_porcao'], 0, ',', '.') : 'falta' }}
                        </td>
                        <td class="{{ $item['custo_unitario'] === null ? 'falta' : '' }}">
                            {{ $item['custo_unitario'] !== null ? 'R$ '.number_format($item['custo_unitario'], 2, ',', '.') : 'falta' }}
                        </td>
                        <td>@if($item['origem_af']) <span class="badge ok">AF</span> @else — @endif</td>
                        <td>
                            @if(! $item['ativo'])
                                <span class="badge fail">inativo</span>
                            @elseif(empty($item['completo']))
                                <span class="badge warn">incompleto</span>
                            @elseif(empty($item['padrao_pnae']))
                                <span class="badge fail">fora PNAE</span>
                            @else
                                <span class="badge ok">ok</span>
                            @endif
                        </td>
                        @if(!empty($podeEditar))
                            <td>
                                <div class="acoes-linha">
                                    <button type="button" class="botao claro"
                                            style="padding:4px 10px;font-size:.78rem"
                                            data-buriti-modal="modal-editar-{{ $item['id'] }}">
                                        <i class="fa fa-pencil"></i> Editar
                                    </button>
                                </div>
                            </td>
                        @endif
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ !empty($podeEditar) ? 9 : 8 }}">
                            Nenhum item. Use <strong>Novo item</strong> ou rode as migrations.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(!empty($podeEditar))
    @include('buriti-ui::operacao-modal', [
        'id' => 'modal-novo-item',
        'titulo' => 'Novo item',
        'lead' => 'Informe peso, kcal e custo — usados no balanço e nas compras.',
        'corpo' => view('alimentacao::partials.item-form', ['item' => []])->render(),
    ])

    @foreach($itens as $item)
        @include('buriti-ui::operacao-modal', [
            'id' => 'modal-editar-'.$item['id'],
            'titulo' => 'Editar · '.$item['nome'],
            'lead' => 'Ajuste nutrição e custo da porção prevista.',
            'corpo' => view('alimentacao::partials.item-form', ['item' => $item])->render(),
        ])
    @endforeach
@endif

@if(!empty($editarId) && !empty($podeEditar))
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (window.buritiAbrirModal) window.buritiAbrirModal('modal-editar-{{ (int) $editarId }}');
});
</script>
@endif
@endsection
