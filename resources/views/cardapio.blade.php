@extends('alimentacao::layout')

@section('titulo', 'Cardápio individual')
@section('migalha', 'Cardápio individual')
@section('icone-tela', 'fa-cutlery')
@section('titulo-tela', 'Montar cardápio do dia')
@section('lead')
    {{ $cardapio['escola'] ?? $escola }} · {{ $data }} · fonte {{ $fonte }}
@endsection

@section('acoes-topo')
    <a class="botao claro" href="{{ url('/alimentacao-escolar/cardapio-lote') }}"><i class="fa fa-calendar"></i> Em lote</a>
    <a class="botao claro" href="{{ url('/alimentacao-escolar/itens') }}"><i class="fa fa-leaf"></i> Itens</a>
@endsection

@section('conteudo')
@php
    $iconeRef = static function (string $nome): array {
        $n = mb_strtolower($nome);
        if (str_contains($n, 'cafe') || str_contains($n, 'café')) return ['classe' => 'cafe', 'icone' => 'fa-coffee', 'rotulo' => 'Café da manhã', 'meta' => 'Começo do dia'];
        if (str_contains($n, 'lanche')) return ['classe' => 'lanche', 'icone' => 'fa-lemon-o', 'rotulo' => 'Lanche', 'meta' => 'Intervalo'];
        return ['classe' => 'almoco', 'icone' => 'fa-cutlery', 'rotulo' => 'Almoço', 'meta' => 'Refeição principal'];
    };
    $vals = ['cafe' => '', 'almoco' => '', 'lanche' => ''];
    foreach (($cardapio['refeicoes'] ?? []) as $ref) {
        $k = mb_strtolower((string) ($ref['refeicao'] ?? ''));
        if (isset($vals[$k])) {
            $vals[$k] = implode(', ', $ref['itens'] ?? []);
        }
    }
    $refeicoesUi = [
        'cafe' => ['rotulo' => 'Café da manhã', 'icone' => 'fa-coffee', 'meta' => 'Começo do dia'],
        'almoco' => ['rotulo' => 'Almoço', 'icone' => 'fa-cutlery', 'meta' => 'Refeição principal'],
        'lanche' => ['rotulo' => 'Lanche', 'icone' => 'fa-lemon-o', 'meta' => 'Intervalo'],
    ];
@endphp

<form class="filtro form-grid" method="get">
    <label>Data <input type="date" name="data" value="{{ $data }}"></label>
    @include('buriti-ui::select-busca', [
        'campo' => 'escola',
        'rotulo' => 'Escola',
        'icone' => 'fa-building',
        'opcoes' => collect($escolas ?? [['cod'=>null,'nome'=>'Rede']])->map(fn ($e) => ['id' => $e['nome'], 'nome' => $e['nome']])->all(),
        'selecionado' => $escola ?? null,
        'placeholder' => 'Buscar escola…',
        'tamanho' => 5,
    ])
    @include('buriti-ui::select-busca', [
        'campo' => 'turma',
        'rotulo' => 'Turma',
        'icone' => 'fa-graduation-cap',
        'opcoes' => collect($turmas ?? [])->map(fn ($t) => ['id' => $t['nome'], 'nome' => $t['nome']])->all(),
        'selecionado' => $turma ?? null,
        'placeholder' => 'Buscar turma…',
        'vazio' => '— escola inteira —',
        'tamanho' => 5,
    ])
    <div class="acoes"><button class="botao" type="submit"><i class="fa fa-search"></i> Filtrar</button></div>
</form>

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

@if(!empty($podeMontar))
<div class="bloco">
    <h2><i class="fa fa-magic"></i> Montar bandejas</h2>
    <p style="margin:0 0 14px;color:var(--suave)">Clique nos itens do catálogo para montar cada refeição. O prato mostra o que será servido.</p>
    <form method="post" action="{{ url('/alimentacao-escolar/cardapio') }}">
        @csrf
        <div class="form-grid" style="margin-bottom:14px">
            <label>Data <input type="date" name="data" value="{{ $data }}" required></label>
            @include('buriti-ui::select-busca', [
                'campo' => 'escola',
                'rotulo' => 'Escola',
                'icone' => 'fa-building',
                'opcoes' => collect($escolas ?? [['cod'=>null,'nome'=>'Rede']])->map(fn ($e) => ['id' => $e['nome'], 'nome' => $e['nome']])->all(),
                'selecionado' => $escola ?? null,
                'placeholder' => 'Buscar escola…',
                'obrigatorio' => true,
                'tamanho' => 5,
            ])
            @include('buriti-ui::select-busca', [
                'campo' => 'turma',
                'rotulo' => 'Turma (opcional)',
                'icone' => 'fa-graduation-cap',
                'opcoes' => collect($turmas ?? [])->map(fn ($t) => [
                    'id' => $t['nome'],
                    'nome' => $t['nome'],
                    'data_id' => $t['id'],
                ])->all(),
                'selecionado' => $turma ?? null,
                'placeholder' => 'Buscar turma…',
                'vazio' => '— escola inteira —',
                'onchange' => "var o=this.options[this.selectedIndex]; document.getElementById('cardapio-turma-id').value=o.dataset.id||0",
                'tamanho' => 5,
            ])
            <input type="hidden" name="turma_id" id="cardapio-turma-id" value="{{ $turma_id ?? 0 }}">
        </div>

        <div class="montagem-grid">
            @foreach($refeicoesUi as $campo => $meta)
                @php
                    $ja = array_values(array_filter(array_map('trim', explode(',', (string) $vals[$campo]))));
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
                    <input type="hidden" name="{{ $campo }}" value="{{ $vals[$campo] }}" data-itens-hidden>
                    @if(!empty($catalogo))
                        <div class="catalogo-chips">
                            @foreach($catalogo as $item)
                                <button type="button"
                                    class="chip-pick @if(in_array($item['nome'], $ja, true)) on @endif"
                                    data-nome="{{ $item['nome'] }}"
                                    title="{{ $item['grupo_pnae'] }}">
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

        <div class="acoes" style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
            <button class="botao" type="submit"><i class="fa fa-save"></i> Salvar cardápio</button>
        </div>
    </form>
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

@if(empty($cardapio['refeicoes']))
    <div class="bloco cena-vazia">
        <i class="fa fa-cutlery"></i>
        <p>Nenhuma refeição nesta data. Monte as bandejas acima ou use o modo em lote.</p>
    </div>
@else
    <div class="bloco" style="border-top-color:var(--destaque)">
        <h2><i class="fa fa-eye"></i> Cardápio publicado</h2>
    </div>
    <div class="bandeja-grid">
        @foreach(($cardapio['refeicoes'] ?? []) as $i => $ref)
            @php $meta = $iconeRef((string) ($ref['refeicao'] ?? 'refeição')); @endphp
            <article class="bandeja {{ $meta['classe'] }}" style="animation-delay: {{ $i * 0.05 }}s">
                <div class="bandeja-topo">
                    <span class="bandeja-ico"><i class="fa {{ $meta['icone'] }}"></i></span>
                    <div>
                        <h2>{{ $meta['rotulo'] }}</h2>
                        <p class="meta">{{ $meta['meta'] }}</p>
                    </div>
                </div>
                <div class="prato">
                    @forelse(($ref['itens'] ?? []) as $item)
                        <span class="chip-item"><i class="fa fa-circle"></i> {{ $item }}</span>
                    @empty
                        <span style="color:var(--suave)">Sem itens</span>
                    @endforelse
                </div>
            </article>
        @endforeach
    </div>
@endif
@endsection
