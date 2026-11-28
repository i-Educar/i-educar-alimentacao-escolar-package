@extends('alimentacao::layout')

@section('titulo', 'Cardápio do dia')
@section('migalha', 'Cardápio do dia')
@section('icone-tela', 'fa-cutlery')
@section('titulo-tela', 'Cardápio do dia')
@section('lead', 'Escopo, destino, montagem das bandejas e validação de horário escolar')

@section('acoes-topo')
    @if(class_exists(\iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::class))
        @include('exportacao::partials.botoes-exportar', [
            'action' => url('/alimentacao-escolar/cardapio/imprimir'),
            'formatos' => ['pdf', 'xlsx', 'csv', 'html'],
            'opcoes' => [
                ['value' => 'cardapio', 'label' => 'Cardápio do dia (institucional)'],
            ],
            'campoExtra' => array_filter([
                'data' => $data ?? null,
                'escola' => $escola ?? null,
                'turma' => $turma ?? null,
                'segmento' => $segmento ?? null,
                'escopo' => $escopo ?? null,
            ], static fn ($v) => $v !== null && $v !== ''),
            'rotulo' => 'Imprimir / baixar',
            'tituloModal' => 'Cardápio escolar',
            'leadModal' => 'PDF no modelo oficial de declaração (cabeçalho, logos, assinaturas e QR).',
            'formatoPadrao' => 'pdf',
        ])
    @else
        <a class="botao claro icone" href="{{ url('/alimentacao-escolar/cardapio/imprimir?'.http_build_query(array_filter(['data'= title="$data,'escola'=>$escola,'turma'=>$turma,'segmento'=>$segmento,'escopo'=>$escopo,'html'=>1]))) }}&quot; target=&quot;_blank&quot; rel=&quot;noopener&quot;> Imprimir" aria-label="$data,'escola'=>$escola,'turma'=>$turma,'segmento'=>$segmento,'escopo'=>$escopo,'html'=>1]))) }}&quot; target=&quot;_blank&quot; rel=&quot;noopener&quot;> Imprimir">$data,'escola'=>$escola,'turma'=>$turma,'segmento'=>$segmento,'escopo'=>$escopo,'html'=>1]))) }}" target="_blank" rel="noopener">
            <i aria-hidden="true" class="fa fa-print"></i></a>
    @endif
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/cardapio-lote') }}" title="Semanal" aria-label="Semanal"><i aria-hidden="true" class="fa fa-calendar"></i></a>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/perfil') }}" title="Porções" aria-label="Porções"><i aria-hidden="true" class="fa fa-building"></i></a>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/itens') }}" title="Itens" aria-label="Itens"><i aria-hidden="true" class="fa fa-leaf"></i></a>
@endsection

@section('conteudo')
@php
    $refeicoesUi = $refeicoesUi ?? [
        'cafe' => ['rotulo' => 'Café da manhã', 'icone' => 'fa-coffee', 'meta' => 'Começo do dia'],
        'almoco' => ['rotulo' => 'Almoço', 'icone' => 'fa-cutlery', 'meta' => 'Refeição principal'],
        'lanche' => ['rotulo' => 'Lanche', 'icone' => 'fa-lemon-o', 'meta' => 'Intervalo'],
    ];
    $escopoAtual = $escopo ?? 'escola';
    $vals = [];
    foreach (array_keys($refeicoesUi) as $k) {
        $vals[$k] = '';
    }
    $temSalvo = false;
    foreach (($cardapio['refeicoes'] ?? []) as $ref) {
        $k = mb_strtolower((string) ($ref['refeicao'] ?? ''));
        if (isset($vals[$k]) && ! empty($ref['itens'])) {
            $vals[$k] = implode(', ', $ref['itens']);
            $temSalvo = true;
        }
    }
    $sugerido = false;
    if (! $temSalvo) {
        $vals = array_merge($vals, $sugestaoVals ?? []);
        $sugerido = ! empty($sugestaoVals);
    }
    $catalogoPorSlot = [];
    foreach (array_keys($refeicoesUi) as $slot) {
        $catalogoPorSlot[$slot] = \iEducar\Packages\BuritiAlimentacaoEscolar\Services\CatalogoItens::filtrarPorRefeicao($catalogo ?? [], $slot);
    }
@endphp
<style>
    .escopo-chips { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 12px; }
    .escopo-chips label {
        display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:999px;
        border:1px solid var(--linha); background:#fff; cursor:pointer; font-size:.8rem; font-weight:700;
    }
    .escopo-chips label:has(input:checked) { border-color:var(--cor); background:color-mix(in srgb, var(--clara) 70%, #fff); color:var(--cor); }
    .escopo-chips input { margin:0; }
    .confirmar-box {
        display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
        margin-top:16px; padding:12px 14px; border-radius:12px;
        background:color-mix(in srgb, var(--clara) 55%, #fff); border:1px solid var(--linha);
    }
    .confirmar-box label { display:flex; align-items:center; gap:8px; font-weight:700; margin:0; }
    .campo-escopo[data-show-for] { display:none !important; }
    .campo-escopo[data-show-for].visivel { display:grid !important; }
    .campo-escopo.col-wide.visivel { display:block !important; }
    .nutri-hint { margin:0 0 12px; font-size:.82rem; color:var(--suave); line-height:1.45; }
    .bandeja.lanche, .bandeja.lanche_2, .bandeja.lanche_3 { --tom-ref: var(--lanche); }
    .bandeja.jantar { --tom-ref: var(--jantar); }
    .multi-check {
        display:grid; grid-template-columns:repeat(auto-fill,minmax(160px,1fr)); gap:6px;
        max-height:180px; overflow:auto; padding:8px; border:1px solid var(--linha);
        border-radius:10px; background:#fff;
    }
    .multi-check label {
        display:flex; gap:8px; align-items:flex-start; font-weight:600; font-size:.82rem; margin:0;
        line-height:1.3; cursor:pointer;
    }
    .multi-check input { margin-top:2px; flex:none; }
    .multi-hint { margin:4px 0 0; font-size:.72rem; color:var(--suave); font-weight:500; }
</style>

@unless($podeMontar)
    <p class="flash alerta">Montagem desabilitada por configuração.</p>
@else
<div class="bloco">
    <h2><i class="fa fa-clone"></i> Data, escopo e destino</h2>
    <form method="post" action="{{ url('/alimentacao-escolar/cardapio') }}" id="form-cardapio-dia">
        @csrf

        <div class="escopo-chips" role="radiogroup" aria-label="Escopo">
            @foreach(($escopos ?? []) as $cod => $rotulo)
                <label>
                    <input type="radio" name="escopo" value="{{ $cod }}" @checked($escopoAtual === $cod) data-escopo-radio>
                    {{ $rotulo }}
                </label>
            @endforeach
        </div>

        <div class="form-grid" style="margin-bottom:14px">
            <label>Data <input type="date" name="data" value="{{ $data }}" required></label>

            <label class="campo-escopo" data-show-for="escola,turma,segmento">Escola
                <select name="escola" data-escola-select id="cardapio-escola-select">
                    @foreach(($escolas ?? []) as $e)
                        @if(($e['nome'] ?? '') !== 'Rede' || true)
                            <option value="{{ $e['nome'] }}" @selected(($escola ?? '') === $e['nome'])>{{ $e['nome'] }}</option>
                        @endif
                    @endforeach
                </select>
            </label>
            <input type="hidden" name="escola" value="Rede" data-escola-rede disabled>

            <label class="col-wide campo-escopo" data-show-for="turma,escola">Turmas
                <div class="multi-check" id="cardapio-turmas-box" role="group" aria-label="Turmas">
                    @forelse(($turmas ?? []) as $t)
                        <label>
                            <input type="checkbox" name="turmas[]" value="{{ (int) $t['id'] }}::{{ $t['nome'] }}"
                                   @checked(($turma ?? '') === $t['nome'] || in_array($t['nome'], $turmasSelecionadas ?? [], true))>
                            {{ $t['nome'] }}
                        </label>
                    @empty
                        <span class="multi-hint" data-turmas-vazio>Selecione a escola para listar as turmas.</span>
                    @endforelse
                </div>
                <p class="multi-hint">Marque uma ou mais turmas. No escopo <em>Turma</em> é obrigatório; no escopo <em>Escola</em> é opcional (vazio = escola inteira).</p>
            </label>

            <label class="col-wide campo-escopo" data-show-for="segmento,escola">Segmentos
                <div class="multi-check" role="group" aria-label="Segmentos">
                    @foreach(($segmentos ?? []) as $cod => $rotulo)
                        <label>
                            <input type="checkbox" name="segmentos[]" value="{{ $cod }}"
                                   @checked(($segmento ?? '') === $cod || in_array($cod, $segmentosSelecionados ?? [], true))>
                            {{ $rotulo }}
                        </label>
                    @endforeach
                </div>
                <p class="multi-hint">Marque um ou mais segmentos. No escopo <em>Segmento</em> é obrigatório. Se turmas e segmentos estiverem marcados no escopo Escola, prevalecem as turmas.</p>
            </label>
        </div>

        <h2 style="margin:8px 0 8px"><i class="fa fa-magic"></i> Montar bandejas</h2>
        <p class="nutri-hint">
            Clique nos itens do catálogo (filtrados pela refeição permitida no cadastro do alimento).
            Lanche pode aparecer mais de uma vez no dia conforme configurações.
            @if($sugerido)
                <strong>Sugestão automática</strong> pré-preenchida.
            @endif
            @if(!empty($confirmado_em))
                · <span class="badge ok">confirmado</span>
            @endif
        </p>

        <div class="montagem-grid">
            @foreach($refeicoesUi as $campo => $meta)
                @php
                    $ja = array_values(array_filter(array_map('trim', explode(',', (string) ($vals[$campo] ?? '')))));
                    $catSlot = $catalogoPorSlot[$campo] ?? ($catalogo ?? []);
                @endphp
                <article class="bandeja {{ $campo }}" data-montagem data-campo="{{ $campo }}">
                    <div class="bandeja-topo">
                        <span class="bandeja-ico"><i class="fa {{ $meta['icone'] }}"></i></span>
                        <div>
                            <h3>{{ $meta['rotulo'] }}</h3>
                            <p class="meta">{{ $meta['meta'] }}</p>
                        </div>
                    </div>
                    <div class="prato" data-prato></div>
                    <input type="hidden" name="{{ $campo }}" value="{{ $vals[$campo] ?? '' }}" data-itens-hidden>
                    @if(!empty($catSlot))
                        <div class="catalogo-chips">
                            @foreach($catSlot as $item)
                                <button type="button"
                                    class="chip-pick @if(in_array($item['nome'], $ja, true)) on @endif"
                                    data-nome="{{ $item['nome'] }}"
                                    title="{{ $item['grupo_pnae'] }}{{ isset($item['peso_porcao_g']) ? ' · '.$item['peso_porcao_g'].'g' : '' }}">
                                    {{ $item['nome'] }}
                                    @if(!empty($item['origem_af'])) <span class="af">AF</span> @endif
                                </button>
                            @endforeach
                        </div>
                    @endif
                    <input class="livre-itens" type="text" placeholder="Itens extras (vírgula)" value="">
                </article>
            @endforeach
        </div>

        <div class="confirmar-box">
            <span style="font-weight:700;color:var(--suave);font-size:.85rem">
                Confirmação marca o cardápio como pronto e estima porções para a unidade registrar os fatos.
            </span>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="botao claro" type="submit" name="acao" value="rascunho">
                    <i class="fa fa-save"></i> Salvar rascunho
                </button>
                <button class="botao" type="submit" name="acao" value="confirmar">
                    <i class="fa fa-check"></i> Salvar e confirmar
                </button>
            </div>
        </div>
    </form>
</div>
<script>
(() => {
    const radios = document.querySelectorAll('[data-escopo-radio]');
    const sync = () => {
        const atual = document.querySelector('[data-escopo-radio]:checked')?.value || 'escola';
        document.querySelectorAll('.campo-escopo').forEach((el) => {
            const show = (el.getAttribute('data-show-for') || '').split(',').map((s) => s.trim());
            const on = show.includes(atual);
            el.classList.toggle('visivel', on);
            el.querySelectorAll('input, select').forEach((inp) => {
                if (inp.hasAttribute('data-escola-rede')) return;
                inp.disabled = !on;
            });
        });
        const redeHidden = document.querySelector('[data-escola-rede]');
        const escolaSelect = document.querySelector('[data-escola-select]');
        if (redeHidden && escolaSelect) {
            const ehRede = atual === 'rede';
            redeHidden.disabled = !ehRede;
            escolaSelect.disabled = ehRede;
            if (!ehRede) escolaSelect.disabled = false;
        }
    };
    radios.forEach((r) => r.addEventListener('change', sync));
    sync();

    const escolaSel = document.getElementById('cardapio-escola-select');
    const turmasBox = document.getElementById('cardapio-turmas-box');
    const selecionadas = @json(array_values($turmasSelecionadas ?? (($turma ?? '') !== '' ? [$turma] : [])));
    const carregarTurmas = () => {
        if (!escolaSel || !turmasBox) return;
        const escola = escolaSel.value;
        if (!escola || escola === 'Rede') {
            turmasBox.innerHTML = '<span class="multi-hint" data-turmas-vazio>Selecione a escola para listar as turmas.</span>';
            return;
        }
        turmasBox.innerHTML = '<span class="multi-hint">Carregando turmas…</span>';
        fetch(@json(url('/alimentacao-escolar/api/turmas')) + '?escola=' + encodeURIComponent(escola), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        }).then((r) => r.json()).then((data) => {
            const turmas = data.turmas || [];
            if (!turmas.length) {
                turmasBox.innerHTML = '<span class="multi-hint">Nenhuma turma ativa nesta escola.</span>';
                return;
            }
            turmasBox.innerHTML = '';
            turmas.forEach((t) => {
                const lab = document.createElement('label');
                const inp = document.createElement('input');
                inp.type = 'checkbox';
                inp.name = 'turmas[]';
                inp.value = (t.id || 0) + '::' + t.nome;
                if (selecionadas.indexOf(t.nome) !== -1) inp.checked = true;
                lab.appendChild(inp);
                lab.appendChild(document.createTextNode(' ' + t.nome));
                turmasBox.appendChild(lab);
            });
            sync();
        }).catch(() => {
            turmasBox.innerHTML = '<span class="multi-hint">Não foi possível carregar as turmas.</span>';
        });
    };
    if (escolaSel) escolaSel.addEventListener('change', carregarTurmas);
})();
</script>
@endunless

@if(!empty($horarios))
<div class="bloco">
    <h2><i class="fa fa-clock-o" style="color:var(--ambar)"></i> Horário escolar × cardápio</h2>
    @foreach($horarios as $a)
        <div class="alerta-card {{ $a['nivel'] === 'ok' ? 'ok' : ($a['nivel'] === 'erro' ? 'erro' : '') }}">
            <span class="selo"><i class="fa fa-{{ $a['nivel'] === 'erro' ? 'times' : 'exclamation' }}"></i></span>
            <div><strong>{{ strtoupper($a['nivel']) }}</strong> · {{ $a['mensagem'] }}</div>
        </div>
    @endforeach
</div>
@endif

@if(!empty($pnae))
<div class="bloco">
    <h2><i class="fa fa-balance-scale"></i> Conformidade PNAE</h2>
    @foreach($pnae as $a)
        <div class="alerta-card {{ $a['nivel'] === 'ok' ? 'ok' : ($a['nivel'] === 'erro' ? 'erro' : '') }}">
            <span class="selo"><i class="fa fa-{{ $a['nivel'] === 'ok' ? 'check' : ($a['nivel'] === 'erro' ? 'times' : 'exclamation') }}"></i></span>
            <div><strong>{{ strtoupper($a['nivel']) }}</strong> · {{ $a['mensagem'] }}</div>
        </div>
    @endforeach
</div>
@endif

@if(!empty($alertas))
<div class="bloco">
    <h2><i class="fa fa-exclamation-triangle" style="color:var(--ambar)"></i> Alertas de restrição</h2>
    @foreach($alertas as $a)
        <div class="alerta-card">
            <span class="selo"><i class="fa fa-ban"></i></span>
            <div><strong>{{ $a['aluno'] }}</strong> — {{ $a['restricao'] }} · itens: {{ implode(', ', $a['itens']) }}</div>
        </div>
    @endforeach
</div>
@endif

<div class="bloco">
    <h2><i class="fa fa-info-circle"></i> Como funciona</h2>
    <p style="margin:0;color:var(--suave);line-height:1.5">
        Escolha o escopo (rede, escola, turma ou segmento) — os campos necessários aparecem ao lado.
        Em <strong>Turma</strong> e <strong>Segmento</strong> (e opcionalmente em Escola) dá para marcar vários destinos de uma vez; o mesmo cardápio é gravado para cada um.
        Ao lançar, o sistema estima porções; a unidade só registra o que foi servido.
        Use <strong>Imprimir / baixar</strong> para o PDF institucional (exportação unificada: cabeçalho, logos, assinaturas e QR).
    </p>
</div>
@endsection
