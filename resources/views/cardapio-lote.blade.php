@extends('alimentacao::layout')

@section('titulo', 'Cardápio semanal')
@section('migalha', 'Cardápio da semana')
@section('icone-tela', 'fa-calendar')
@section('titulo-tela', 'Cardápio diário ou semanal')
@section('lead', 'Aplique o mesmo cardápio à rede, escolas, turmas ou segmentos em um intervalo')

@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/cardapio') }}" title="Do dia" aria-label="Do dia"><i aria-hidden="true" class="fa fa-cutlery"></i></a>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/perfil') }}" title="Porções" aria-label="Porções"><i aria-hidden="true" class="fa fa-building"></i></a>
@endsection

@section('conteudo')
@php
    $refeicoesUi = $refeicoesUi ?? [
        'cafe' => ['rotulo' => 'Café da manhã', 'icone' => 'fa-coffee', 'meta' => 'Começo do dia'],
        'almoco' => ['rotulo' => 'Almoço', 'icone' => 'fa-cutlery', 'meta' => 'Refeição principal'],
        'lanche' => ['rotulo' => 'Lanche', 'icone' => 'fa-lemon-o', 'meta' => 'Intervalo'],
    ];
    $escopoAtual = $escopo ?? 'escola';
@endphp
<style>
    .escopo-chips { display:flex; flex-wrap:wrap; gap:8px; margin:0 0 12px; }
    .escopo-chips label {
        display:inline-flex; align-items:center; gap:6px; padding:7px 12px; border-radius:999px;
        border:1px solid var(--linha); background:#fff; cursor:pointer; font-size:.8rem; font-weight:700;
    }
    .escopo-chips label:has(input:checked) { border-color:var(--cor); background:color-mix(in srgb, var(--clara) 70%, #fff); color:var(--cor); }
    .escopo-chips input { margin:0; }
    .escolas-lote { display:grid; grid-template-columns:repeat(auto-fill,minmax(180px,1fr)); gap:6px; max-height:180px; overflow:auto; padding:8px; border:1px solid var(--linha); border-radius:10px; background:#fff; }
    .escolas-lote label { display:flex; gap:8px; align-items:center; font-weight:600; font-size:.85rem; margin:0; }
    .confirmar-box {
        display:flex; align-items:center; justify-content:space-between; gap:12px; flex-wrap:wrap;
        margin-top:16px; padding:12px 14px; border-radius:12px;
        background:color-mix(in srgb, var(--clara) 55%, #fff); border:1px solid var(--linha);
    }
    .confirmar-box label { display:flex; align-items:center; gap:8px; font-weight:700; margin:0; }
    .campo-escopo[data-show-for] { display:none !important; }
    .campo-escopo[data-show-for].visivel { display:grid !important; }
    .campo-escopo.col-wide.visivel { display:block !important; }
    .bandeja.lanche, .bandeja.lanche_2, .bandeja.lanche_3 { --tom-ref: var(--lanche); }
</style>

@unless($podeMontar)
    <p class="flash alerta">Montagem desabilitada por configuração.</p>
@else
<div class="bloco">
    <h2><i class="fa fa-clone"></i> Período, escopo e destino</h2>
    <form method="post" action="{{ url('/alimentacao-escolar/cardapio-lote') }}" id="form-cardapio-lote">
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
            <label>Data início <input type="date" name="data_inicio" value="{{ $data_inicio }}" required></label>
            <label>Data fim <input type="date" name="data_fim" value="{{ $data_fim }}" required></label>
            <label class="col-wide campo-escopo" data-show-for="turma,escola">Turmas (opcional)
                <textarea name="turmas_texto" rows="2" placeholder="Uma ou mais turmas, separadas por vírgula — ex.: 1º A, 2º B, 3º C"></textarea>
                <small style="color:var(--suave);font-weight:500">No escopo Turma, informe ao menos uma. Vazio no escopo Escola = unidade inteira.</small>
            </label>
            <label class="col-wide campo-escopo" data-show-for="segmento,escola">Segmentos
                <div class="escolas-lote">
                    @foreach(($segmentos ?? []) as $cod => $rotulo)
                        <label>
                            <input type="checkbox" name="segmentos[]" value="{{ $cod }}"
                                   @checked(($segmento ?? '') === $cod)>
                            {{ $rotulo }}
                        </label>
                    @endforeach
                </div>
                <small style="color:var(--suave);font-weight:500">Marque um ou mais. Obrigatório no escopo Segmento. Se houver turmas e segmentos, prevalecem as turmas.</small>
            </label>
            <label class="col-wide campo-escopo" data-show-for="escola,turma,segmento">Escolas
                <div class="escolas-lote">
                    @foreach(($escolas ?? []) as $e)
                        <label>
                            <input type="checkbox" name="escolas[]" value="{{ $e['nome'] }}"
                                   @checked($e['nome'] === 'Rede' || ($escopoAtual === 'escola' && $loop->index < 2))>
                            {{ $e['nome'] }}
                        </label>
                    @endforeach
                </div>
            </label>
        </div>

        <h2 style="margin:8px 0 8px"><i class="fa fa-magic"></i> Montar bandejas</h2>
        <p style="margin:0 0 12px;font-size:.82rem;color:var(--suave);line-height:1.45">
            Clique nos itens do catálogo. Se gravar sem montar, o sistema monta automaticamente café, almoço e lanche com o catálogo PNAE.
        </p>
        <div class="montagem-grid">
            @foreach($refeicoesUi as $campo => $meta)
                @php
                    $catSlot = \iEducar\Packages\BuritiAlimentacaoEscolar\Services\CatalogoItens::filtrarPorRefeicao($catalogo ?? [], $campo);
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
                    <input type="hidden" name="{{ $campo }}" value="" data-itens-hidden>
                    @if(!empty($catSlot))
                        <div class="catalogo-chips">
                            @foreach($catSlot as $item)
                                <button type="button" class="chip-pick" data-nome="{{ $item['nome'] }}"
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
                Confirmação aplica o lote como executável no período selecionado.
            </span>
            <div style="display:flex;gap:8px;flex-wrap:wrap">
                <button class="botao claro" type="submit" name="acao" value="rascunho">
                    <i class="fa fa-save"></i> Gravar rascunho
                </button>
                <button class="botao" type="submit" name="acao" value="confirmar">
                    <i class="fa fa-check"></i> Gravar e confirmar lote
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
            el.classList.toggle('visivel', show.includes(atual));
            el.querySelectorAll('input, select').forEach((inp) => { inp.disabled = !show.includes(atual); });
        });
    };
    radios.forEach((r) => r.addEventListener('change', sync));
    sync();
})();
</script>
@endif

<div class="bloco">
    <h2><i class="fa fa-info-circle"></i> Como combinar os filtros</h2>
    <p style="margin:0;color:var(--suave);line-height:1.5">
        <strong>Rede</strong> grava um cardápio único para toda a rede.
        <strong>Escola</strong> exige marcar uma ou mais escolas.
        <strong>Turma</strong> aplica às escolas marcadas para uma ou mais turmas informadas.
        <strong>Segmento</strong> vincula o cardápio a um ou mais segmentos (Infantil, Fundamental, Médio etc.).
        Pesos e valores nutricionais previstos vêm do catálogo de itens e entram no balanço de compras.
        Sem bandejas montadas, a automação preenche as três refeições ao gravar.
    </p>
</div>
@endsection
