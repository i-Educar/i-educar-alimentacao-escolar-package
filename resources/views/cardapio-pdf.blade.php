<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Cardápio escolar — {{ $cardapio['escola'] ?? '' }} — {{ $cardapio['data'] ?? '' }}</title>
    <style>
        :root {
            --cor: {{ $identidade['cor'] ?? '#0f6b4c' }};
            --clara: {{ $identidade['clara'] ?? '#e5f6ec' }};
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 24px;
            font-family: "Segoe UI", Georgia, serif;
            color: #1a242b; background: #f4f7f5;
        }
        .folha {
            max-width: 800px; margin: 0 auto; background: #fff;
            border: 1px solid #c9dbd0; border-radius: 8px;
            padding: 28px 32px 40px;
            box-shadow: 0 8px 24px rgba(21,34,43,.06);
        }
        .topo { display:flex; justify-content:space-between; gap:16px; align-items:flex-start; border-bottom:3px solid var(--cor); padding-bottom:14px; margin-bottom:18px; }
        .topo h1 { margin:0; font-size:1.45rem; color:var(--cor); letter-spacing:-.02em; }
        .topo .meta { margin:6px 0 0; color:#5b6b76; font-size:.9rem; line-height:1.4; }
        .selo { text-align:right; font-size:.75rem; color:#5b6b76; text-transform:uppercase; letter-spacing:.08em; font-weight:700; }
        .refeicao { margin:0 0 16px; border:1px solid #d7e5dc; border-radius:10px; overflow:hidden; }
        .refeicao header { background:var(--clara); color:var(--cor); padding:8px 14px; font-weight:800; font-size:.95rem; }
        .refeicao ul { margin:0; padding:10px 14px 12px 32px; }
        .refeicao li { margin:4px 0; font-size:.95rem; }
        .vazio { color:#5b6b76; font-style:italic; padding:12px 14px; margin:0; }
        .assinatura {
            margin-top:36px; display:grid; grid-template-columns:1fr 1fr; gap:28px;
            page-break-inside: avoid;
        }
        .assinatura .campo { text-align:center; padding-top:48px; }
        .assinatura .linha { border-top:1px solid #1a242b; margin:0 12px 8px; }
        .assinatura .nome { font-weight:700; font-size:.9rem; }
        .assinatura .detalhe { font-size:.78rem; color:#5b6b76; }
        .rodape { margin-top:28px; font-size:.72rem; color:#5b6b76; text-align:center; border-top:1px dashed #c9dbd0; padding-top:10px; }
        .acoes-print { max-width:800px; margin:0 auto 12px; display:flex; gap:8px; justify-content:flex-end; }
        .acoes-print button, .acoes-print a {
            display:inline-flex; align-items:center; gap:6px; padding:8px 12px; border-radius:8px;
            border:1px solid #c9dbd0; background:#fff; color:var(--cor); font:inherit; font-weight:700; cursor:pointer; text-decoration:none;
        }
        @media print {
            body { background:#fff; padding:0; }
            .folha { box-shadow:none; border:0; border-radius:0; max-width:none; }
            .acoes-print { display:none !important; }
        }
    </style>
</head>
<body>
<div class="acoes-print">
    <button type="button" onclick="window.print()"><i class="fa fa-print"></i> Imprimir / salvar PDF</button>
    <a href="{{ url('/alimentacao-escolar/cardapio?'.http_build_query(array_filter(['data'=>$cardapio['data']??null,'escola'=>$cardapio['escola']??null]))) }}">Voltar</a>
</div>

<article class="folha">
    <header class="topo">
        <div>
            <h1>Cardápio escolar</h1>
            <p class="meta">
                <strong>{{ $cardapio['escola'] ?? 'Rede' }}</strong><br>
                Data: {{ isset($cardapio['data']) ? \Illuminate\Support\Carbon::parse($cardapio['data'])->format('d/m/Y') : '—' }}
                @if(!empty($cardapio['turma'])) · Turma: {{ $cardapio['turma'] }} @endif
                @if(!empty($cardapio['segmento']))
                    · Segmento: {{ ($segmentos[$cardapio['segmento']] ?? $cardapio['segmento']) }}
                @endif
            </p>
        </div>
        <div class="selo">{{ $identidade['nome'] ?? 'Alimentação escolar' }}<br>Divulgação</div>
    </header>

    @php
        $porSlot = [];
        foreach (($cardapio['refeicoes'] ?? []) as $ref) {
            $k = mb_strtolower((string) ($ref['refeicao'] ?? ''));
            $porSlot[$k] = $ref['itens'] ?? [];
        }
    @endphp

    @forelse(($slots ?? []) as $cod => $meta)
        <section class="refeicao">
            <header>{{ $meta['rotulo'] ?? $cod }}</header>
            @if(!empty($porSlot[$cod]))
                <ul>
                    @foreach($porSlot[$cod] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            @else
                <p class="vazio">Sem itens nesta refeição.</p>
            @endif
        </section>
    @empty
        <p class="vazio">Nenhum cardápio encontrado para os filtros.</p>
    @endforelse

    <div class="assinatura">
        <div class="campo">
            <div class="linha"></div>
            @if(!empty($rt['nome']))
                <div class="nome">{{ $rt['nome'] }}</div>
                <div class="detalhe">{{ $rt['cargo'] ?? 'Responsável técnico' }}@if(!empty($rt['registro'])) · {{ $rt['registro'] }}@endif</div>
            @else
                <div class="nome">Responsável técnico</div>
                <div class="detalhe">Cadastre o RT em Configurações</div>
            @endif
        </div>
        <div class="campo">
            <div class="linha"></div>
            <div class="nome">Direção / Unidade</div>
            <div class="detalhe">Ciência e divulgação</div>
        </div>
    </div>

    <p class="rodape">
        Visual legado · {{ now()->format('d/m/Y H:i') }}.
        Preferir o download institucional em Cardápio do dia → «Imprimir / baixar» (módulo Exportação unificada).
    </p>
</article>
<link rel="stylesheet" href="/intranet/styles/font-awesome.css">
</body>
</html>
