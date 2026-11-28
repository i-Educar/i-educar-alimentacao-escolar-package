@extends('alimentacao::layout')

@section('titulo', 'Mapa de distribuição')
@section('migalha', 'Mapa')
@section('icone-tela', 'fa-map-o')
@section('titulo-tela', 'Mapa de distribuição')
@section('lead')
    Visão geral da rede e detalhe por escola
@endsection

@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar') }}" title="Painel" aria-label="Painel"><i aria-hidden="true" class="fa fa-th-large"></i></a>
    <a class="botao icone" href="{{ url('/alimentacao-escolar/cardapio') }}" title="Cardápio" aria-label="Cardápio"><i aria-hidden="true" class="fa fa-cutlery"></i></a>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/balanco') }}" title="Balanço" aria-label="Balanço"><i aria-hidden="true" class="fa fa-balance-scale"></i></a>
@endsection

@section('foco')
@php
    $rede = $mapa['rede'] ?? ['escolas' => 0, 'total' => 0, 'linhas' => 0, 'por_refeicao' => []];
    $unidades = $mapa['unidades'] ?? [];
    $fonteRotulo = ($fonte ?? 'demo') === 'demo' ? 'dados de demonstração' : 'cardápios cadastrados';
@endphp
<section class="buriti-foco">
    <p class="buriti-foco-kicker">Rede</p>
    <h2 class="buriti-foco-titulo">
        {{ $rede['escolas'] }} escola{{ $rede['escolas'] === 1 ? '' : 's' }} · {{ number_format($rede['total'], 0, ',', '.') }} porções
    </h2>
    <p class="buriti-foco-texto">
        Fonte: {{ $fonteRotulo }}{{ !empty($porMatriculas) ? ' · ponderado por matrículas' : '' }}.
        Clique numa escola para ver o detalhe das refeições.
    </p>
</section>
@endsection

@section('conteudo')
@php
    use iEducar\Packages\BuritiAlimentacaoEscolar\Services\MapaDistribuicao;
    $rede = $mapa['rede'] ?? ['escolas' => 0, 'total' => 0, 'linhas' => 0, 'por_refeicao' => []];
    $unidades = $mapa['unidades'] ?? [];
    $maxUnidade = max(1, ...array_map(static fn ($e) => (int) ($e['total'] ?? 0), $unidades ?: [['total' => 1]]));
    $foco = $escolaFoco ?? '';
    $primeira = $unidades[0]['nome'] ?? '';
    if ($foco === '' && $primeira !== '') {
        $foco = $primeira;
    }
@endphp
<style>
    .mapa-shell {
        display: grid; grid-template-columns: minmax(0, 1.15fr) minmax(280px, .85fr);
        gap: 16px; align-items: start;
    }
    .mapa-geral .ranking { list-style: none; margin: 0; padding: 0; display: grid; gap: 10px; }
    .mapa-escola-btn {
        display: grid; grid-template-columns: 36px 1fr auto; gap: 12px; align-items: center;
        width: 100%; text-align: left; cursor: pointer;
        padding: 12px 14px; border-radius: 14px; border: 1px solid var(--linha);
        background: #fff; color: var(--tinta); font: inherit;
        transition: border-color .15s ease, box-shadow .15s ease, transform .15s ease;
    }
    .mapa-escola-btn:hover { transform: translateY(-1px); border-color: color-mix(in srgb, var(--cor) 35%, var(--linha)); }
    .mapa-escola-btn.ativo {
        border-color: color-mix(in srgb, var(--cor) 45%, var(--linha));
        box-shadow: 0 0 0 2px color-mix(in srgb, var(--cor) 18%, transparent);
        background: linear-gradient(120deg, color-mix(in srgb, var(--clara) 55%, #fff), #fff);
    }
    .mapa-escola-btn .ord {
        width: 36px; height: 36px; border-radius: 11px; display: grid; place-items: center;
        background: color-mix(in srgb, var(--clara) 70%, #fff); color: var(--cor); font-weight: 800; font-size: .85rem;
    }
    .mapa-escola-btn.ativo .ord { background: var(--cor); color: #fff; }
    .mapa-escola-btn strong { display: block; font-size: .95rem; }
    .mapa-escola-btn small { display: block; color: var(--suave); margin-top: 2px; font-size: .78rem; font-weight: 500; }
    .mapa-escola-btn .pct { font-weight: 800; color: var(--cor); font-size: .9rem; white-space: nowrap; }
    .mapa-escola-btn .mini-trilha {
        grid-column: 2 / -1; height: 6px; border-radius: 999px; background: #e8f0ea; overflow: hidden; margin-top: 2px;
    }
    .mapa-escola-btn .mini-trilha i {
        display: block; height: 100%; width: var(--pct, 0%); border-radius: inherit;
        background: linear-gradient(90deg, var(--destaque), var(--cor));
    }
    .mapa-detalhe {
        position: sticky; top: 72px;
        background: #fff; border: 1px solid var(--linha); border-radius: 16px;
        border-top: 4px solid var(--cor); padding: 16px 18px;
        box-shadow: 0 12px 28px rgba(21,34,43,.06); min-height: 280px;
    }
    .mapa-detalhe[hidden] { display: none !important; }
    .mapa-detalhe .det-kicker {
        margin: 0 0 4px; font-size: .7rem; font-weight: 800; letter-spacing: .1em;
        text-transform: uppercase; color: var(--cor);
    }
    .mapa-detalhe h2 { margin: 0 0 6px; font-size: 1.15rem; line-height: 1.25; word-break: break-word; }
    .mapa-detalhe .det-lead { margin: 0 0 14px; color: var(--suave); font-size: .88rem; }
    .mapa-detalhe .det-metas { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 14px; }
    .mapa-detalhe .barra-dist { margin: 0 0 14px; }
    .mapa-detalhe .det-acoes { display: flex; flex-wrap: wrap; gap: 8px; }
    .mapa-rede-ref { display: flex; flex-wrap: wrap; gap: 8px; margin: 0 0 4px; }
    .mapa-chip-ref {
        display: inline-flex; align-items: center; gap: 6px; padding: 6px 10px; border-radius: 999px;
        background: color-mix(in srgb, var(--clara) 55%, #fff); border: 1px solid var(--linha);
        font-size: .78rem; font-weight: 700;
    }
    .mapa-chip-ref strong { color: var(--cor); }
    @media (max-width: 920px) {
        .mapa-shell { grid-template-columns: 1fr; }
        .mapa-detalhe { position: static; }
    }
</style>

@if(empty($unidades))
    <div class="bloco cena-vazia">
        <i class="fa fa-truck"></i>
        <p>Sem distribuição por escola. Monte um cardápio ou rode a demo.</p>
        <p style="margin-top:12px">
            <a class="botao" href="{{ url('/alimentacao-escolar/cardapio') }}"><i class="fa fa-cutlery"></i> Montar cardápio</a>
        </p>
    </div>
@else
    <div class="numeros">
        <article style="--tom: var(--cor)">
            <div class="kpi-ico"><i class="fa fa-building"></i></div>
            <span>Escolas na rede</span>
            <strong>{{ $rede['escolas'] }}</strong>
        </article>
        <article style="--tom: var(--almoco, var(--cor))">
            <div class="kpi-ico"><i class="fa fa-cutlery"></i></div>
            <span>Porções (geral)</span>
            <strong>{{ number_format($rede['total'], 0, ',', '.') }}</strong>
        </article>
        <article style="--tom: var(--cafe, #c17a2f)">
            <div class="kpi-ico"><i class="fa fa-clock-o"></i></div>
            <span>Linhas escola × refeição</span>
            <strong>{{ $rede['linhas'] }}</strong>
        </article>
    </div>

    @if(!empty($rede['por_refeicao']))
        <div class="bloco">
            <h2><i class="fa fa-pie-chart"></i> Geral da rede · por refeição</h2>
            <div class="mapa-rede-ref">
                @foreach($rede['por_refeicao'] as $ref => $qtd)
                    <span class="mapa-chip-ref">
                        {{ MapaDistribuicao::rotuloRefeicao($ref) }}
                        <strong>{{ number_format($qtd, 0, ',', '.') }}</strong>
                    </span>
                @endforeach
            </div>
        </div>
    @endif

    <div class="mapa-shell">
        <section class="bloco mapa-geral">
            <h2><i class="fa fa-sitemap"></i> Escolas · ranking</h2>
            <p style="margin:0 0 12px;color:var(--suave);font-size:.86rem">Selecione uma unidade para abrir o detalhe ao lado.</p>
            <ul class="ranking" role="list">
                @foreach($unidades as $i => $e)
                    @php $pctBarra = (int) round(($e['total'] / $maxUnidade) * 100); @endphp
                    <li>
                        <button type="button"
                                class="mapa-escola-btn {{ $foco === $e['nome'] ? 'ativo' : '' }}"
                                data-mapa-escola="{{ $e['nome'] }}"
                                aria-pressed="{{ $foco === $e['nome'] ? 'true' : 'false' }}">
                            <span class="ord">{{ $i + 1 }}</span>
                            <span>
                                <strong>{{ $e['nome'] }}</strong>
                                <small>{{ number_format($e['total'], 0, ',', '.') }} porções · {{ count($e['itens']) }} refeição(ões)</small>
                                <span class="mini-trilha" aria-hidden="true"><i style="--pct: {{ $pctBarra }}%"></i></span>
                            </span>
                            <span class="pct">{{ number_format($e['pct_rede'], 1, ',', '.') }}%</span>
                        </button>
                    </li>
                @endforeach
            </ul>
        </section>

        <aside class="mapa-detalhe" id="mapa-detalhe" aria-live="polite">
            <p class="det-kicker">Detalhe da escola</p>
            <h2 data-det-nome>—</h2>
            <p class="det-lead" data-det-lead>Selecione uma escola na lista.</p>
            <div class="det-metas" data-det-metas></div>
            <ul class="barra-dist" data-det-barras></ul>
            <div class="det-acoes" data-det-acoes></div>
        </aside>
    </div>

    <script type="application/json" id="mapa-escolas-json">{!! json_encode(
        collect($unidades)->mapWithKeys(static function (array $e) {
            return [$e['nome'] => [
                'nome' => $e['nome'],
                'total' => $e['total'],
                'pct_rede' => $e['pct_rede'],
                'itens' => $e['itens'],
                'max_item' => $e['max_item'],
                'datas' => $e['datas'],
            ]];
        })->all(),
        JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS
    ) !!}</script>
    <script>
    (function () {
        var raw = document.getElementById('mapa-escolas-json');
        if (!raw) return;
        var dados = {};
        try { dados = JSON.parse(raw.textContent || '{}'); } catch (e) { return; }
        var painel = document.getElementById('mapa-detalhe');
        var rotulos = {
            cafe: 'Café da manhã', almoco: 'Almoço', lanche: 'Lanche', jantar: 'Jantar'
        };
        function rotulo(ref) {
            return rotulos[(ref || '').toLowerCase()] || (ref ? ref.charAt(0).toUpperCase() + ref.slice(1) : 'Refeição');
        }
        function abrir(nome) {
            var e = dados[nome];
            if (!e || !painel) return;
            document.querySelectorAll('[data-mapa-escola]').forEach(function (btn) {
                var on = btn.getAttribute('data-mapa-escola') === nome;
                btn.classList.toggle('ativo', on);
                btn.setAttribute('aria-pressed', on ? 'true' : 'false');
            });
            painel.querySelector('[data-det-nome]').textContent = e.nome;
            var datas = (e.datas || []).slice(0, 3).join(', ');
            painel.querySelector('[data-det-lead]').textContent =
                (e.pct_rede || 0).toLocaleString('pt-BR', { maximumFractionDigits: 1 }) + '% da rede'
                + (datas ? ' · cardápios em ' + datas : '');
            var metas = painel.querySelector('[data-det-metas]');
            metas.innerHTML =
                '<span class="badge ok"><i class="fa fa-cutlery"></i> ' + (e.total || 0).toLocaleString('pt-BR') + ' porções</span>'
                + '<span class="badge warn"><i class="fa fa-pie-chart"></i> ' + Object.keys(e.itens || {}).length + ' refeições</span>';
            var barras = painel.querySelector('[data-det-barras]');
            var max = Math.max(1, e.max_item || 1);
            var html = '';
            Object.keys(e.itens || {}).forEach(function (ref) {
                var q = e.itens[ref] || 0;
                var pct = Math.round((q / max) * 100);
                html += '<li><div class="rotulo"><span>' + rotulo(ref) + '</span><strong>' + q.toLocaleString('pt-BR') + '</strong></div>'
                    + '<div class="trilha"><i style="--pct:' + pct + '%"></i></div></li>';
            });
            barras.innerHTML = html || '<li style="color:var(--suave)">Sem refeições nesta escola.</li>';
            var qs = encodeURIComponent(e.nome);
            painel.querySelector('[data-det-acoes]').innerHTML =
                '<a class="botao" href="{{ url('/alimentacao-escolar/cardapio') }}?escola=' + qs + '"><i class="fa fa-cutlery"></i> Cardápio</a>'
                + '<a class="botao claro" href="{{ url('/alimentacao-escolar/perfil') }}?escola=' + qs + '"><i class="fa fa-building"></i> Porções</a>'
                + '<a class="botao claro" href="{{ url('/alimentacao-escolar/balanco') }}?escola=' + qs + '"><i class="fa fa-balance-scale"></i> Balanço</a>';
            try {
                var u = new URL(window.location.href);
                u.searchParams.set('escola', e.nome);
                history.replaceState(null, '', u.toString());
            } catch (err) {}
            if (window.matchMedia && window.matchMedia('(max-width: 920px)').matches) {
                painel.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        }
        document.querySelectorAll('[data-mapa-escola]').forEach(function (btn) {
            btn.addEventListener('click', function () { abrir(btn.getAttribute('data-mapa-escola')); });
        });
        var inicial = @json($foco);
        if (inicial && dados[inicial]) abrir(inicial);
        else {
            var primeiro = Object.keys(dados)[0];
            if (primeiro) abrir(primeiro);
        }
    })();
    </script>
@endif
@endsection
