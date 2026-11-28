<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <title>Cronograma de Cardápios — {{ $escola ?? '' }} — {{ \Illuminate\Support\Carbon::parse($inicio)->format('d/m/Y') }} a {{ \Illuminate\Support\Carbon::parse($fim)->format('d/m/Y') }}</title>
    <style>
        :root {
            --cor: {{ $identidade['cor'] ?? '#0f6b4c' }};
            --clara: {{ $identidade['clara'] ?? '#e5f6ec' }};
        }
        * { box-sizing: border-box; }
        body {
            margin: 0; padding: 20px;
            font-family: "Segoe UI", Georgia, serif;
            color: #1a242b; background: #f4f7f5;
        }
        .folha {
            max-width: 1000px; margin: 0 auto; background: #fff;
            border: 1px solid #c9dbd0; border-radius: 8px;
            padding: 24px 28px 36px;
            box-shadow: 0 8px 24px rgba(21,34,43,.06);
        }
        .topo {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            border-bottom: 3px solid var(--cor);
            padding-bottom: 12px;
            margin-bottom: 16px;
        }
        .topo h1 {
            margin: 0;
            font-size: 1.4rem;
            color: var(--cor);
            letter-spacing: -.02em;
        }
        .topo .meta {
            margin: 6px 0 0;
            color: #5b6b76;
            font-size: .88rem;
            line-height: 1.4;
        }
        .selo {
            text-align: right;
            font-size: .72rem;
            color: #5b6b76;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-weight: 700;
        }
        
        .dia-bloco {
            margin: 0 0 20px;
            border: 1px solid #d7e5dc;
            border-radius: 10px;
            overflow: hidden;
            page-break-inside: avoid;
        }
        .dia-bloco .dia-header {
            background: var(--cor);
            color: #fff;
            padding: 8px 14px;
            font-weight: 700;
            font-size: .95rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .dia-bloco .dia-header .nutricao-mini {
            font-size: .78rem;
            font-weight: 400;
            opacity: .9;
        }
        .refeicoes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1px;
            background: #d7e5dc;
        }
        .refeicao-cell {
            background: #fff;
            padding: 10px 12px;
        }
        .refeicao-cell .refeicao-nome {
            font-weight: 700;
            font-size: .85rem;
            color: var(--cor);
            margin-bottom: 4px;
        }
        .refeicao-cell ul {
            margin: 0;
            padding: 0 0 0 16px;
            font-size: .82rem;
        }
        .refeicao-cell li {
            margin: 2px 0;
        }
        .vazio {
            color: #5b6b76;
            font-style: italic;
            padding: 8px 12px;
            margin: 0;
            background: #f9fafb;
        }

        .resumo-nutricional {
            margin: 20px 0;
            padding: 16px;
            background: var(--clara);
            border-radius: 8px;
            border: 1px solid #c9dbd0;
        }
        .resumo-nutricional h3 {
            margin: 0 0 12px;
            font-size: .95rem;
            color: var(--cor);
        }
        .resumo-nutricional .nutri-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 10px;
        }
        .resumo-nutricional .nutri-item {
            text-align: center;
            padding: 8px;
            background: #fff;
            border-radius: 6px;
            border: 1px solid #d7e5dc;
        }
        .resumo-nutricional .nutri-valor {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--cor);
        }
        .resumo-nutricional .nutri-label {
            font-size: .72rem;
            color: #5b6b76;
            margin-top: 2px;
        }

        .restricoes-box {
            margin: 16px 0;
            padding: 14px;
            background: #fff9e6;
            border-radius: 8px;
            border: 1px solid #f4d574;
        }
        .restricoes-box h3 {
            margin: 0 0 10px;
            font-size: .9rem;
            color: #c97d04;
        }
        .restricoes-box ul {
            margin: 0;
            padding: 0 0 0 20px;
            font-size: .82rem;
            color: #5b6b76;
        }
        .restricoes-box li {
            margin: 4px 0;
        }

        .assinatura {
            margin-top: 32px;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            page-break-inside: avoid;
        }
        .assinatura .campo {
            text-align: center;
            padding-top: 44px;
        }
        .assinatura .linha {
            border-top: 1px solid #1a242b;
            margin: 0 12px 8px;
        }
        .assinatura .nome {
            font-weight: 700;
            font-size: .88rem;
        }
        .assinatura .detalhe {
            font-size: .75rem;
            color: #5b6b76;
        }

        .rodape {
            margin-top: 24px;
            font-size: .7rem;
            color: #5b6b76;
            text-align: center;
            border-top: 1px dashed #c9dbd0;
            padding-top: 10px;
        }

        .acoes-print {
            max-width: 1000px;
            margin: 0 auto 12px;
            display: flex;
            gap: 8px;
            justify-content: flex-end;
        }
        .acoes-print button, .acoes-print a {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            border: 1px solid #c9dbd0;
            background: #fff;
            color: var(--cor);
            font: inherit;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
        }

        @media print {
            body { background: #fff; padding: 0; }
            .folha { box-shadow: none; border: 0; border-radius: 0; max-width: none; }
            .acoes-print { display: none !important; }
        }
    </style>
</head>
<body>
<div class="acoes-print">
    <button type="button" onclick="window.print()"><i class="fa fa-print"></i> Imprimir / salvar PDF</button>
    <a href="{{ url('/alimentacao-escolar/cardapio-lote?'.http_build_query(array_filter(['data_inicio'=>$inicio,'data_fim'=>$fim,'escola'=>$escola]))) }}">Voltar</a>
</div>

<article class="folha">
    <header class="topo">
        <div>
            <h1>Cronograma de Cardápios</h1>
            <p class="meta">
                <strong>{{ $escola ?? 'Rede' }}</strong><br>
                Período: {{ \Illuminate\Support\Carbon::parse($inicio)->format('d/m/Y') }} a {{ \Illuminate\Support\Carbon::parse($fim)->format('d/m/Y') }}
                @if(!empty($turma)) · Turma: {{ $turma }} @endif
                @if(!empty($segmento))
                    · Segmento: {{ ($segmentos[$segmento] ?? $segmento) }}
                @endif
            </p>
        </div>
        <div class="selo">{{ $identidade['nome'] ?? 'Alimentação escolar' }}<br>Cronograma</div>
    </header>

    @if(!empty($mediasNutricao))
        <div class="resumo-nutricional">
            <h3>Resumo Nutricional Médio (por dia)</h3>
            <div class="nutri-grid">
                <div class="nutri-item">
                    <div class="nutri-valor">{{ number_format($mediasNutricao['kcal'], 0, ',', '.') }}</div>
                    <div class="nutri-label">kcal</div>
                </div>
                <div class="nutri-item">
                    <div class="nutri-valor">{{ number_format($mediasNutricao['proteina'], 1, ',', '.') }}g</div>
                    <div class="nutri-label">Proteínas</div>
                </div>
                <div class="nutri-item">
                    <div class="nutri-valor">{{ number_format($mediasNutricao['carboidrato'], 1, ',', '.') }}g</div>
                    <div class="nutri-label">Carboidratos</div>
                </div>
                <div class="nutri-item">
                    <div class="nutri-valor">{{ number_format($mediasNutricao['lipidio'], 1, ',', '.') }}g</div>
                    <div class="nutri-label">Lipídios</div>
                </div>
                <div class="nutri-item">
                    <div class="nutri-valor">{{ number_format($mediasNutricao['fibra'], 1, ',', '.') }}g</div>
                    <div class="nutri-label">Fibras</div>
                </div>
            </div>
        </div>
    @endif

    @if(!empty($restricoes))
        <div class="restricoes-box">
            <h3><i class="fa fa-exclamation-triangle"></i> Restrições Alimentares Ativas ({{ count($restricoes) }})</h3>
            <ul>
                @foreach(array_slice($restricoes, 0, 10) as $r)
                    <li><strong>{{ $r['restricao'] }}</strong> — Aluno #{{ $r['aluno_id'] }}@if(!empty($r['observacao'])) ({{ $r['observacao'] }})@endif</li>
                @endforeach
                @if(count($restricoes) > 10)
                    <li><em>+ {{ count($restricoes) - 10 }} outras restrições cadastradas</em></li>
                @endif
            </ul>
        </div>
    @endif

    @forelse($cronograma as $dia)
        <div class="dia-bloco">
            <div class="dia-header">
                <span>
                    {{ \Illuminate\Support\Carbon::parse($dia['data'])->locale('pt_BR')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}
                </span>
                @if(!empty($dia['nutricao']['kcal']))
                    <span class="nutricao-mini">
                        {{ number_format($dia['nutricao']['kcal'], 0, ',', '.') }} kcal
                    </span>
                @endif
            </div>

            @php
                $porSlot = [];
                foreach (($dia['refeicoes'] ?? []) as $ref) {
                    $k = mb_strtolower((string) ($ref['refeicao'] ?? ''));
                    $porSlot[$k] = $ref['itens'] ?? [];
                }
            @endphp

            <div class="refeicoes-grid">
                @forelse(($slots ?? []) as $cod => $meta)
                    @if(!empty($porSlot[$cod]))
                        <div class="refeicao-cell">
                            <div class="refeicao-nome">{{ $meta['rotulo'] ?? $cod }}</div>
                            <ul>
                                @foreach($porSlot[$cod] as $item)
                                    <li>{{ $item }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                @empty
                @endforelse
            </div>

            @if(empty($dia['refeicoes']))
                <p class="vazio">Sem cardápio registrado para esta data.</p>
            @endif
        </div>
    @empty
        <p class="vazio">Nenhum cardápio encontrado para o período selecionado.</p>
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
        Documento gerado pelo módulo de Alimentação escolar · {{ now()->format('d/m/Y H:i') }}.
        Use «Imprimir» do navegador e escolha «Salvar como PDF» para arquivo digital.
    </p>
</article>
<link rel="stylesheet" href="/intranet/styles/font-awesome.css">
</body>
</html>
