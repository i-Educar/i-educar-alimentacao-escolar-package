@extends('alimentacao::layout')

@section('titulo', 'Balanço e compras')
@section('migalha', 'Balanço')
@section('icone-tela', 'fa-balance-scale')
@section('titulo-tela', 'Balanço de consumo e compras')
@section('lead', 'Previsto × servido, custo, kcal e incongruências / itens fora do padrão')

@section('acoes-topo')
    <button type="button" class="botao consulta icone" data-buriti-modal="modal-filtro-balanco" title="Período" aria-label="Período"><i aria-hidden="true" class="fa fa-filter"></i></button>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/perfil') }}" title="Perfil" aria-label="Perfil"><i aria-hidden="true" class="fa fa-building"></i></a>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/itens') }}" title="Itens" aria-label="Itens"><i aria-hidden="true" class="fa fa-leaf"></i></a>
@endsection

@section('foco')
@php $b = $balanco ?? ['totais'=>[], 'itens'=>[], 'incongruencias'=>[], 'por_escola'=>[]]; @endphp
<section class="buriti-foco">
    <p class="buriti-foco-kicker">Balanço</p>
    <h2 class="buriti-foco-titulo">
        {{ $inicio }} → {{ $fim }}
        @if(!empty($escola)) · {{ $escola }} @else · rede @endif
    </h2>
    <p class="buriti-foco-texto">
        Custo previsto R$ {{ number_format($b['totais']['custo_previsto'] ?? 0, 2, ',', '.') }}
        · {{ count($b['itens'] ?? []) }} item(ns)
        @if(!empty($b['incongruencias'])) · {{ count($b['incongruencias']) }} alerta(s) @endif
    </p>
</section>
@endsection

@section('conteudo')
@php $b = $balanco ?? ['totais'=>[], 'itens'=>[], 'incongruencias'=>[], 'por_escola'=>[]]; @endphp
<style>
    .bal-tabela-wrap { overflow-x: auto; -webkit-overflow-scrolling: touch; max-width: 100%; }
    .bal-tabela { width: 100%; min-width: 680px; border-collapse: collapse; }
    .bal-tabela th, .bal-tabela td { text-align: left; padding: 8px 10px; border-bottom: 1px solid var(--linha); white-space: nowrap; }
    .bal-tabela th { background: var(--clara); color: var(--cor); font-size: .78rem; }
    .bal-tabela td.item { white-space: normal; max-width: 160px; font-weight: 700; }
    .bal-filtros { display:grid; grid-template-columns: repeat(3,minmax(140px,1fr)) auto; gap:8px; align-items:end; }
    .bal-filtros label { display:grid; gap:4px; font-size:.75rem; font-weight:700; color:var(--suave); }
    .bal-filtros input, .bal-filtros select { min-height:36px; padding:6px 8px; border:1px solid var(--linha); border-radius:8px; font:inherit; background:#fff; max-width:100%; }
</style>

@include('buriti-ui::operacao-modal', [
    'id' => 'modal-filtro-balanco',
    'titulo' => 'Período e escola',
    'lead' => 'Ajuste o recorte do balanço sem sair da visão.',
    'corpo' => view('alimentacao::partials.balanco-filtro', [
        'inicio' => $inicio,
        'fim' => $fim,
        'escola' => $escola ?? '',
        'escolas' => $escolas ?? [],
    ])->render(),
])

<div class="numeros">
    <article style="--tom: var(--cor)">
        <div class="kpi-ico"><i class="fa fa-cutlery"></i></div>
        <span>Porções previstas</span>
        <strong>{{ number_format($b['totais']['previstas'] ?? 0, 0, ',', '.') }}</strong>
    </article>
    <article style="--tom: #0e7490">
        <div class="kpi-ico"><i class="fa fa-check"></i></div>
        <span>Porções servidas</span>
        <strong>{{ number_format($b['totais']['servidas'] ?? 0, 0, ',', '.') }}</strong>
    </article>
    <article style="--tom: var(--ambar)">
        <div class="kpi-ico"><i class="fa fa-money"></i></div>
        <span>Custo previsto</span>
        <strong>R$ {{ number_format($b['totais']['custo_previsto'] ?? 0, 2, ',', '.') }}</strong>
    </article>
    <article style="--tom: var(--ok)">
        <div class="kpi-ico"><i class="fa fa-shopping-cart"></i></div>
        <span>Custo servido</span>
        <strong>R$ {{ number_format($b['totais']['custo_servido'] ?? 0, 2, ',', '.') }}</strong>
    </article>
    <article style="--tom: #7c3aed">
        <div class="kpi-ico"><i class="fa fa-fire"></i></div>
        <span>kcal previsto</span>
        <strong>{{ number_format($b['totais']['kcal_previsto'] ?? 0, 0, ',', '.') }}</strong>
    </article>
    @if(isset($b['adesao']))
    <article style="--tom: #059669">
        <div class="kpi-ico"><i class="fa fa-percent"></i></div>
        <span>Adesão ao cardápio</span>
        <strong>{{ number_format($b['adesao']['percentual'] ?? 0, 1, ',', '.') }}%</strong>
    </article>
    @endif
</div>

@if(isset($b['adesao']) && !empty($b['adesao']['por_escola']))
<div class="bloco adesao-secao">
    <h2><i class="fa fa-users"></i> Indicador de Adesão ao Cardápio (5.2.3)</h2>
    <div class="adesao-info">
        <p>
            <strong>{{ number_format($b['adesao']['percentual'], 1, ',', '.') }}%</strong> de adesão geral
            · {{ number_format($b['adesao']['porcoes_servidas'], 0, ',', '.') }} porções servidas
            de {{ number_format($b['adesao']['expectativa'], 0, ',', '.') }} esperadas
            ({{ number_format($b['adesao']['matriculas'], 0, ',', '.') }} matrículas × {{ $b['adesao']['dias_uteis'] }} dias úteis)
        </p>
    </div>
    
    @if(count($b['adesao']['por_escola']) > 1)
    <div class="bal-tabela-wrap" style="margin-top: 16px;">
        <table class="bal-tabela">
            <thead>
                <tr>
                    <th>Escola</th>
                    <th style="text-align: right;">Matrículas</th>
                    <th style="text-align: right;">Porções Servidas</th>
                    <th style="text-align: right;">Expectativa</th>
                    <th style="text-align: right;">Adesão</th>
                </tr>
            </thead>
            <tbody>
                @foreach($b['adesao']['por_escola'] as $esc)
                <tr>
                    <td class="item">{{ $esc['escola'] }}</td>
                    <td style="text-align: right;">{{ number_format($esc['matriculas'], 0, ',', '.') }}</td>
                    <td style="text-align: right;">{{ number_format($esc['porcoes_servidas'], 0, ',', '.') }}</td>
                    <td style="text-align: right;">{{ number_format($esc['expectativa'], 0, ',', '.') }}</td>
                    <td style="text-align: right;">
                        <span class="badge-adesao badge-adesao--{{ $esc['percentual'] >= 70 ? 'ok' : ($esc['percentual'] >= 50 ? 'alerta' : 'baixo') }}">
                            {{ number_format($esc['percentual'], 1, ',', '.') }}%
                        </span>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>

<style>
.adesao-secao {
    background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
    border: 2px solid #059669;
}
.adesao-info {
    background: #fff;
    padding: 12px 16px;
    border-radius: 8px;
    border: 1px solid #d1fae5;
}
.adesao-info p {
    margin: 0;
    font-size: 0.95rem;
    color: #065f46;
    line-height: 1.6;
}
.adesao-info strong {
    font-size: 1.3rem;
    color: #059669;
}
.badge-adesao {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.85rem;
}
.badge-adesao--ok {
    background: #d1fae5;
    color: #065f46;
}
.badge-adesao--alerta {
    background: #fef3c7;
    color: #92400e;
}
.badge-adesao--baixo {
    background: #fee2e2;
    color: #991b1b;
}
</style>
@endif

@if(!empty($b['incongruencias']))
<div class="bloco">
    <h2><i class="fa fa-exclamation-triangle" style="color:var(--ambar)"></i> Incongruências e fora do padrão ({{ count($b['incongruencias']) }})</h2>
    @foreach($b['incongruencias'] as $inc)
        <div class="alerta-card {{ ($inc['nivel'] ?? '') === 'erro' ? 'erro' : '' }}">
            <span class="selo"><i class="fa fa-{{ ($inc['nivel'] ?? '') === 'erro' ? 'times' : 'exclamation' }}"></i></span>
            <div><strong>{{ strtoupper($inc['nivel'] ?? 'alerta') }}</strong> · {{ $inc['mensagem'] }}</div>
        </div>
    @endforeach
</div>
@endif

<div class="bloco">
    <h2><i class="fa fa-shopping-basket"></i> Previsão de compras / consumo por item</h2>
    <div class="bal-tabela-wrap">
        <table class="bal-tabela">
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Peso (g)</th>
                    <th>Prev. (kg)</th>
                    <th>Serv. (kg)</th>
                    <th>Custo prev.</th>
                    <th>Custo serv.</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($b['itens'] ?? []) as $item)
                    <tr>
                        <td class="item">{{ $item['item'] }}</td>
                        <td>{{ $item['peso_g'] ? number_format($item['peso_g'], 0, ',', '.') : '—' }}</td>
                        <td>{{ number_format($item['peso_previsto_kg'], 2, ',', '.') }}</td>
                        <td>{{ number_format($item['peso_servido_kg'], 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($item['custo_previsto'], 2, ',', '.') }}</td>
                        <td>R$ {{ number_format($item['custo_servido'], 2, ',', '.') }}</td>
                        <td>
                            @if(!empty($item['fora_padrao']))
                                <span class="badge fail">fora padrão</span>
                            @elseif(!empty($item['sem_nutricao']))
                                <span class="badge warn">sem nutrição</span>
                            @else
                                <span class="badge ok">ok</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7">Sem cardápios no período — monte cardápios e registre porções no perfil escolar.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if(!empty($b['por_escola']))
<div class="bloco">
    <h2><i class="fa fa-building"></i> Por escola</h2>
    <div class="card-grid">
        @foreach($b['por_escola'] as $e)
            <article class="card">
                <header>
                    <span class="ico"><i class="fa fa-university"></i></span>
                    <div>
                        <h3>{{ $e['escola'] }}</h3>
                        <p class="meta">previstas {{ $e['porcoes_previstas'] }} · servidas {{ $e['porcoes_servidas'] }}</p>
                    </div>
                </header>
            </article>
        @endforeach
    </div>
</div>
@endif
@endsection
