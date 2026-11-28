@extends('alimentacao::layout')

@section('titulo', 'Fichas técnicas')
@section('migalha', 'Fichas técnicas')
@section('icone-tela', 'fa-file-text-o')
@section('titulo-tela', 'Fichas técnicas de preparações')
@section('lead', 'Preparações com ingredientes, per capita, modo de preparo e composição nutricional (TR 5.2.7–5.2.9)')

@section('acoes-topo')
    @include('exportacao::partials.botoes-exportar', [
        'action' => url('/alimentacao-escolar/fichas-tecnicas/exportar'),
        'formatos' => ['pdf', 'xlsx', 'csv'],
        'opcoes' => [
            ['value' => 'fichas', 'label' => 'Fichas técnicas (visão atual)'],
        ],
        'campoExtra' => ['escola' => $escola ?? ''],
        'tituloModal' => 'Download — Fichas técnicas',
    ])
    @if(!empty($podeEditar))
        <a class="botao icone" href="{{ url('/alimentacao-escolar/fichas-tecnicas/nova') }}" title="Nova ficha" aria-label="Nova ficha"><i aria-hidden="true" class="fa fa-plus"></i></a>
    @endif
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/itens') }}" title="Catálogo" aria-label="Catálogo"><i aria-hidden="true" class="fa fa-list"></i></a>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/cardapio') }}" title="Cardápio" aria-label="Cardápio"><i aria-hidden="true" class="fa fa-cutlery"></i></a>
@endsection

@section('conteudo')
<section class="bloco">
    <form method="get" class="form-grid" style="margin:0 0 14px">
        <label>Escola
            <select name="escola" onchange="this.form.submit()">
                <option value="">Todas as escolas</option>
                @foreach($escolas as $esc)
                    <option value="{{ $esc['nome'] }}" @selected($escola === $esc['nome'])>{{ $esc['nome'] }}</option>
                @endforeach
            </select>
        </label>
    </form>

    @if(empty($fichas))
        <p style="color:var(--suave);margin:12px 0">Nenhuma ficha técnica cadastrada ainda.</p>
        @if(!empty($podeEditar))
            <a class="botao claro" href="{{ url('/alimentacao-escolar/fichas-tecnicas/nova') }}">Criar primeira ficha</a>
        @endif
    @else
        <div class="fichas-grid">
            @foreach($fichas as $ficha)
                <article class="ficha-card">
                    <header>
                        <h3>{{ $ficha['nome'] }}</h3>
                        <span class="badge ok">{{ ucfirst($ficha['categoria']) }}</span>
                    </header>
                    <div class="corpo">
                        <p><i class="fa fa-building"></i> {{ $ficha['escola'] }}</p>
                        @if($ficha['rendimento_porcoes'] > 0)
                            <p><i class="fa fa-pie-chart"></i> {{ $ficha['rendimento_porcoes'] }} porções</p>
                        @endif
                        @if($ficha['tempo_preparo_min'] > 0)
                            <p><i class="fa fa-clock-o"></i> {{ $ficha['tempo_preparo_min'] }} min</p>
                        @endif
                        @if($ficha['kcal_porcao'] > 0)
                            <p><i class="fa fa-fire"></i> {{ number_format($ficha['kcal_porcao'], 0, ',', '.') }} kcal/porção</p>
                        @endif
                        <p><i class="fa fa-list"></i> {{ count($ficha['ingredientes'] ?? []) }} ingredientes</p>
                    </div>
                    <footer>
                        <a class="botao claro" href="{{ url('/alimentacao-escolar/fichas-tecnicas/'.$ficha['id'].'/editar') }}">
                            <i class="fa fa-pencil"></i> Editar
                        </a>
                        @if(!empty($podeEditar))
                            <button type="button" class="botao claro" onclick="abrirModalCopiar({{ (int) $ficha['id'] }}, @json($ficha['nome']))">
                                <i class="fa fa-copy"></i> Copiar
                            </button>
                            <form method="post" action="{{ url('/alimentacao-escolar/fichas-tecnicas/'.$ficha['id'].'/excluir') }}"
                                  onsubmit="return confirm('Desativar esta ficha técnica?')">
                                @csrf
                                <button type="submit" class="botao claro" style="color:var(--erro)"><i class="fa fa-trash"></i></button>
                            </form>
                        @endif
                    </footer>
                </article>
            @endforeach
        </div>
    @endif
</section>

<div id="modalCopiar" class="modal-ficha" hidden>
    <div class="modal-ficha-box">
        <header>
            <h3>Copiar ficha técnica</h3>
            <button type="button" class="botao claro" onclick="fecharModalCopiar()">&times;</button>
        </header>
        <form method="post" action="{{ url('/alimentacao-escolar/fichas-tecnicas/copiar') }}">
            @csrf
            <input type="hidden" name="ficha_id" id="copiar_ficha_id">
            <p>Copiando: <strong id="copiar_ficha_nome"></strong></p>
            <p class="campo-rotulo">Escolas destino</p>
            <div class="checkbox-grupo">
                @foreach($escolas as $esc)
                    @if(($esc['nome'] ?? '') !== 'Rede')
                        <label class="checkbox-item">
                            <input type="checkbox" name="escolas[]" value="{{ $esc['nome'] }}">
                            <span>{{ $esc['nome'] }}</span>
                        </label>
                    @endif
                @endforeach
            </div>
            <div class="modal-ficha-acoes">
                <button type="button" class="botao claro" onclick="fecharModalCopiar()">Cancelar</button>
                <button type="submit" class="botao"><i class="fa fa-copy"></i> Copiar</button>
            </div>
        </form>
    </div>
</div>

<style>
.fichas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 12px; }
.ficha-card { background: #fff; border: 1px solid var(--linha); border-radius: 14px; border-top: 3px solid var(--cor-tela); overflow: hidden; }
.ficha-card header { display: flex; justify-content: space-between; gap: 8px; align-items: start; padding: 12px 14px; background: var(--clara); }
.ficha-card h3 { margin: 0; font-size: .98rem; color: var(--cor); }
.ficha-card .corpo { padding: 12px 14px; }
.ficha-card .corpo p { margin: 0 0 6px; color: var(--suave); font-size: .88rem; }
.ficha-card .corpo i { width: 18px; color: var(--cor); }
.ficha-card footer { display: flex; flex-wrap: wrap; gap: 8px; padding: 10px 14px; border-top: 1px solid var(--linha); background: color-mix(in srgb, var(--clara) 40%, #fff); }
.modal-ficha { position: fixed; inset: 0; background: rgba(0,0,0,.45); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-ficha[hidden] { display: none !important; }
.modal-ficha-box { background: #fff; border-radius: 14px; max-width: 480px; width: 92%; padding: 16px 18px; border-top: 3px solid var(--cor); }
.modal-ficha-box header { display: flex; justify-content: space-between; align-items: center; margin: 0 0 12px; }
.modal-ficha-box h3 { margin: 0; color: var(--cor); }
.checkbox-grupo { display: grid; gap: 8px; max-height: 240px; overflow: auto; margin: 8px 0 14px; }
.checkbox-item { display: flex; gap: 8px; align-items: center; padding: 8px 10px; border: 1px solid var(--linha); border-radius: 8px; }
.modal-ficha-acoes { display: flex; justify-content: flex-end; gap: 8px; }
.campo-rotulo { font-weight: 700; font-size: .82rem; color: var(--suave); }
</style>
<script>
function abrirModalCopiar(id, nome) {
    document.getElementById('copiar_ficha_id').value = id;
    document.getElementById('copiar_ficha_nome').textContent = nome;
    document.getElementById('modalCopiar').hidden = false;
}
function fecharModalCopiar() {
    document.getElementById('modalCopiar').hidden = true;
}
</script>
@endsection
