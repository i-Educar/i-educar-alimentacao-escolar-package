@extends('alimentacao::layout')

@section('titulo', 'Cardápio em lote')
@section('migalha', 'Cardápio em lote')
@section('icone-tela', 'fa-calendar')
@section('titulo-tela', 'Cardápio em lote')
@section('lead', 'Mesmo cardápio para várias escolas em um intervalo de datas')

@section('acoes-topo')
    <a class="botao claro" href="{{ url('/alimentacao-escolar/cardapio') }}"><i class="fa fa-cutlery"></i> Individual</a>
    <a class="botao claro" href="{{ url('/alimentacao-escolar/itens') }}"><i class="fa fa-leaf"></i> Itens</a>
@endsection

@section('conteudo')
@php
    $refeicoesUi = [
        'cafe' => ['rotulo' => 'Café da manhã', 'icone' => 'fa-coffee', 'meta' => 'Começo do dia'],
        'almoco' => ['rotulo' => 'Almoço', 'icone' => 'fa-cutlery', 'meta' => 'Refeição principal'],
        'lanche' => ['rotulo' => 'Lanche', 'icone' => 'fa-lemon-o', 'meta' => 'Intervalo'],
    ];
@endphp

@unless($podeMontar)
    <p class="flash alerta">Montagem desabilitada por configuração.</p>
@else
<div class="bloco">
    <h2><i class="fa fa-clone"></i> Período e escolas</h2>
    <form method="post" action="{{ url('/alimentacao-escolar/cardapio-lote') }}">
        @csrf
        <div class="form-grid" style="margin-bottom:14px">
            <label>Data início <input type="date" name="data_inicio" value="{{ $data_inicio }}" required></label>
            <label>Data fim <input type="date" name="data_fim" value="{{ $data_fim }}" required></label>
            <label>Turma (opcional) <input name="turma" placeholder="vazio = escola inteira"></label>
            <label class="col-wide">Escolas
                <div class="escolas-lote">
                    @foreach(($escolas ?? []) as $e)
                        <label>
                            <input type="checkbox" name="escolas[]" value="{{ $e['nome'] }}" @checked($e['nome'] === 'Rede')>
                            {{ $e['nome'] }}
                        </label>
                    @endforeach
                </div>
            </label>
        </div>

        <h2 style="margin:8px 0 12px"><i class="fa fa-magic"></i> Montar bandejas</h2>
        <div class="montagem-grid">
            @foreach($refeicoesUi as $campo => $meta)
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
                    @if(!empty($catalogo))
                        <div class="catalogo-chips">
                            @foreach($catalogo as $item)
                                <button type="button" class="chip-pick" data-nome="{{ $item['nome'] }}" title="{{ $item['grupo_pnae'] }}">
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

        <div style="display:flex;justify-content:flex-end;gap:8px;margin-top:16px">
            <button class="botao" type="submit"><i class="fa fa-save"></i> Gravar lote</button>
        </div>
    </form>
</div>
@endif

<div class="bloco">
    <h2><i class="fa fa-info-circle"></i> Como funciona</h2>
    <p style="margin:0;color:var(--suave);line-height:1.5">
        O lote aplica o mesmo conjunto de refeições a todas as escolas marcadas, em cada dia do intervalo
        @if(config('alimentacao.lote_somente_uteis', true)) (somente dias úteis) @endif.
        Ao gravar, as regras PNAE (fruta/hortaliça, ultraprocessados, agricultura familiar) são avaliadas.
    </p>
</div>
@endsection
