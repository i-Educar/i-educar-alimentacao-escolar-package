@extends('alimentacao::layout')

@section('titulo', 'Mapa de distribuição')
@section('migalha', 'Mapa')
@section('icone-tela', 'fa-map-o')
@section('titulo-tela', 'Mapa de distribuição')
@section('lead')
    Fonte: {{ $fonte ?? 'demo' }}@if(!empty($porMatriculas)) · ponderado por matrículas@endif · total {{ $mapa['total'] ?? 0 }} porções
@endsection

@section('acoes-topo')
    <a class="botao claro" href="{{ url('/alimentacao-escolar') }}"><i class="fa fa-th-large"></i> Painel</a>
    <a class="botao" href="{{ url('/alimentacao-escolar/cardapio') }}"><i class="fa fa-cutlery"></i> Cardápio</a>
@endsection

@section('conteudo')
    @php
        $porChave = $mapa['por_chave'] ?? [];
        $escolas = [];
        foreach ($porChave as $chave => $qtd) {
            $partes = preg_split('/\s*\|\s*/', (string) $chave, 2);
            $escola = trim($partes[0] ?? 'Escola');
            $refeicao = trim($partes[1] ?? 'Refeição');
            $escolas[$escola]['itens'][$refeicao] = (int) $qtd;
            $escolas[$escola]['total'] = ($escolas[$escola]['total'] ?? 0) + (int) $qtd;
        }
        $maxGlobal = max(1, (int) ($mapa['total'] ?? 1));
        foreach ($escolas as $nome => $dados) {
            $vals = array_values($dados['itens'] ?? [1]);
            $escolas[$nome]['max'] = max(1, ...$vals);
        }
        uasort($escolas, static fn ($a, $b) => ($b['total'] ?? 0) <=> ($a['total'] ?? 0));
    @endphp

    <div class="numeros">
        <article style="--tom: var(--almoco)">
            <div class="kpi-ico"><i class="fa fa-building"></i></div>
            <span>Escolas</span>
            <strong>{{ count($escolas) }}</strong>
        </article>
        <article style="--tom: var(--lanche)">
            <div class="kpi-ico"><i class="fa fa-cutlery"></i></div>
            <span>Porções</span>
            <strong>{{ $mapa['total'] ?? 0 }}</strong>
        </article>
        <article style="--tom: var(--cafe)">
            <div class="kpi-ico"><i class="fa fa-clock-o"></i></div>
            <span>Linhas refeição</span>
            <strong>{{ count($porChave) }}</strong>
        </article>
    </div>

    @if($escolas === [])
        <div class="bloco cena-vazia">
            <i class="fa fa-truck"></i>
            <p>Sem distribuição cadastrada. Monte um cardápio ou rode a demo para ver as escolas como estações de entrega.</p>
            <p style="margin-top:12px">
                <a class="botao" href="{{ url('/alimentacao-escolar/cardapio') }}"><i class="fa fa-cutlery"></i> Montar cardápio</a>
            </p>
        </div>
    @else
        <div class="mapa-rede">
            @foreach($escolas as $nome => $dados)
                <article class="escola-card" style="animation-delay: {{ $loop->index * 0.04 }}s">
                    <header>
                        <span class="predio"><i class="fa fa-university"></i></span>
                        <div>
                            <h3>{{ $nome }}</h3>
                            <p class="qtd">{{ $dados['total'] }} porções · {{ (int) round(($dados['total'] / $maxGlobal) * 100) }}% da rede</p>
                        </div>
                    </header>
                    <ul class="barra-dist">
                        @foreach(($dados['itens'] ?? []) as $refeicao => $qtd)
                            @php $pct = (int) round(($qtd / max(1, $dados['max'])) * 100); @endphp
                            <li>
                                <div class="rotulo"><span>{{ ucfirst($refeicao) }}</span><strong>{{ $qtd }}</strong></div>
                                <div class="trilha"><i style="--pct: {{ $pct }}%"></i></div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="trilha" style="margin-top:12px;height:6px" title="Peso na rede">
                        <i style="--pct: {{ (int) round(($dados['total'] / $maxGlobal) * 100) }}%;background:linear-gradient(90deg,#c17a2f,var(--cor))"></i>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
