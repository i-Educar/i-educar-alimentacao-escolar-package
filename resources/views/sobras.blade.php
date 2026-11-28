@extends('alimentacao::layout')

@section('titulo', 'Sobras e desperdício')
@section('migalha', 'Sobras')
@section('icone-tela', 'fa-trash')
@section('titulo-tela', 'Sobras, restos e indicadores')
@section('lead', 'TR 5.2.21–24 — registro de sobra limpa/suja/resto e % de desperdício por período')

@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/sobras?inicio='.$inicio.'&fim='.$fim.'&escola='.urlencode($escola).'&formato=csv') }}" title="CSV" aria-label="CSV"><i aria-hidden="true" class="fa fa-download"></i></a>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/sobras?inicio='.$inicio.'&fim='.$fim.'&escola='.urlencode($escola).'&formato=pdf') }}" title="PDF" aria-label="PDF"><i aria-hidden="true" class="fa fa-file-pdf-o"></i></a>
@endsection

@section('conteudo')
@php $ind = $indicadores ?? []; @endphp

@unless($schemaOk ?? false)
    <div class="flash alerta">Schema <code>alimentacao.sobra_registro</code> ausente — rode as migrations do pacote.</div>
@endunless

<div class="numeros">
    <article><span>Registros</span><strong>{{ $ind['total_registros'] ?? 0 }}</strong></article>
    <article style="--tom:var(--ambar)"><span>Sobra limpa (kg)</span><strong>{{ number_format($ind['sobra_limpa_kg'] ?? 0, 2, ',', '.') }}</strong></article>
    <article style="--tom:var(--erro)"><span>Sobra suja (kg)</span><strong>{{ number_format($ind['sobra_suja_kg'] ?? 0, 2, ',', '.') }}</strong></article>
    <article style="--tom:var(--cafe)"><span>Resto (kg)</span><strong>{{ number_format($ind['resto_kg'] ?? 0, 2, ',', '.') }}</strong></article>
    <article style="--tom:var(--ok)"><span>Desperdício</span><strong>{{ isset($ind['desperdicio_pct']) ? $ind['desperdicio_pct'].'%' : '—' }}</strong></article>
</div>

<div class="bloco">
    <h2><i class="fa fa-plus-circle"></i> Registrar sobra / resto</h2>
    <form method="post" action="{{ url('/alimentacao-escolar/sobras') }}" class="form-grid">
        @csrf
        <label>Escola
            <select name="escola" required>
                <option value="">Selecione…</option>
                @foreach($escolas ?? [] as $e)
                    <option value="{{ $e }}" @selected($escola === $e)>{{ $e }}</option>
                @endforeach
                @if($escola !== '' && !in_array($escola, $escolas ?? [], true))
                    <option value="{{ $escola }}" selected>{{ $escola }}</option>
                @endif
            </select>
        </label>
        <label>Data <input type="date" name="data" value="{{ date('Y-m-d') }}" required></label>
        <label>Refeição
            <select name="refeicao">
                <option value="cafe">Café</option>
                <option value="almoco" selected>Almoço</option>
                <option value="lanche">Lanche</option>
                <option value="jantar">Jantar</option>
            </select>
        </label>
        <label>Sobra limpa (kg) <input type="number" step="0.01" min="0" name="sobra_limpa_kg"></label>
        <label>Sobra suja (kg) <input type="number" step="0.01" min="0" name="sobra_suja_kg"></label>
        <label>Resto (kg) <input type="number" step="0.01" min="0" name="resto_kg"></label>
        <label>Porções produzidas <input type="number" min="0" name="porcoes_produzidas"></label>
        <label>Porções servidas <input type="number" min="0" name="porcoes_servidas"></label>
        <label class="col-wide">Observação <input type="text" name="observacao" maxlength="500"></label>
        <div class="acoes"><button class="botao" type="submit"><i class="fa fa-save"></i> Salvar</button></div>
    </form>
</div>

<div class="bloco">
    <h2><i class="fa fa-filter"></i> Período</h2>
    <form method="get" action="{{ url('/alimentacao-escolar/sobras') }}" class="filtro">
        <label>Início <input type="date" name="inicio" value="{{ $inicio }}"></label>
        <label>Fim <input type="date" name="fim" value="{{ $fim }}"></label>
        <label>Escola
            <select name="escola">
                <option value="">Rede</option>
                @foreach($escolas ?? [] as $e)
                    <option value="{{ $e }}" @selected($escola === $e)>{{ $e }}</option>
                @endforeach
            </select>
        </label>
        <button class="botao claro" type="submit"><i class="fa fa-search"></i> Filtrar</button>
    </form>
</div>

<div class="bloco">
    <h2><i class="fa fa-list"></i> Registros</h2>
    @if(empty($registros))
        <p style="margin:0;color:var(--suave)">Nenhum registro no período.</p>
    @else
        <table>
            <thead>
            <tr>
                <th>Data</th><th>Escola</th><th>Refeição</th>
                <th>Limpa</th><th>Suja</th><th>Resto</th>
                <th>Prod.</th><th>Serv.</th>
            </tr>
            </thead>
            <tbody>
            @foreach($registros as $r)
                <tr>
                    <td>{{ $r['data'] ?? '' }}</td>
                    <td>{{ $r['escola'] ?? '' }}</td>
                    <td>{{ $r['refeicao'] ?? '' }}</td>
                    <td>{{ $r['sobra_limpa_kg'] ?? '—' }}</td>
                    <td>{{ $r['sobra_suja_kg'] ?? '—' }}</td>
                    <td>{{ $r['resto_kg'] ?? '—' }}</td>
                    <td>{{ $r['porcoes_produzidas'] ?? '—' }}</td>
                    <td>{{ $r['porcoes_servidas'] ?? '—' }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    @endif
</div>
@endsection
