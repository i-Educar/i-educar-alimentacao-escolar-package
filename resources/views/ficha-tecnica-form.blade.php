@extends('alimentacao::layout')

@php
    $editando = isset($ficha) && is_array($ficha);
    $itensCatalogo = $itens ?? [];
@endphp

@section('titulo', $editando ? 'Editar ficha técnica' : 'Nova ficha técnica')
@section('migalha', 'Fichas técnicas')
@section('icone-tela', 'fa-file-text-o')
@section('titulo-tela', $editando ? 'Editar ficha técnica' : 'Nova ficha técnica')
@section('lead', 'Itens do catálogo, per capita, modo de preparo e composição nutricional automática')

@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/fichas-tecnicas') }}" title="Voltar" aria-label="Voltar"><i aria-hidden="true" class="fa fa-arrow-left"></i></a>
@endsection

@section('conteudo')
@if($editando && (($ficha['kcal_porcao'] ?? 0) > 0 || ($ficha['proteina_g'] ?? 0) > 0))
<section class="numeros" style="margin:0 0 14px">
    <article>
        <span>kcal/porção</span>
        <strong>{{ number_format((float) $ficha['kcal_porcao'], 0, ',', '.') }}</strong>
    </article>
    <article style="--tom:#0f766e">
        <span>Proteína (g)</span>
        <strong>{{ number_format((float) ($ficha['proteina_g'] ?? 0), 1, ',', '.') }}</strong>
    </article>
    <article style="--tom:#1d4ed8">
        <span>Carboidrato (g)</span>
        <strong>{{ number_format((float) ($ficha['carboidrato_g'] ?? 0), 1, ',', '.') }}</strong>
    </article>
    <article style="--tom:#b86e00">
        <span>Custo/porção</span>
        <strong>R$ {{ number_format((float) ($ficha['custo_porcao'] ?? 0), 2, ',', '.') }}</strong>
    </article>
</section>
@endif

<section class="bloco">
    <form method="post" action="{{ url('/alimentacao-escolar/fichas-tecnicas') }}" id="form-ficha">
        @csrf
        @if($editando)
            <input type="hidden" name="id" value="{{ $ficha['id'] }}">
        @endif

        <h2><i class="fa fa-info-circle"></i> Informações básicas</h2>
        <div class="form-grid">
            <label class="col-wide">Nome da preparação *
                <input type="text" name="nome" required maxlength="180"
                       value="{{ old('nome', $ficha['nome'] ?? '') }}"
                       placeholder="Ex: Arroz integral com legumes">
            </label>
            <label>Escola
                <select name="escola">
                    <option value="">Todas / rede</option>
                    @foreach($escolas as $esc)
                        <option value="{{ $esc['nome'] }}" @selected(old('escola', $ficha['escola'] ?? '') === $esc['nome'])>
                            {{ $esc['nome'] }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>Categoria
                <select name="categoria">
                    @foreach($categorias as $valor => $nome)
                        <option value="{{ $valor }}" @selected(old('categoria', $ficha['categoria'] ?? 'principal') === $valor)>
                            {{ $nome }}
                        </option>
                    @endforeach
                </select>
            </label>
            <label>Tempo de preparo (min)
                <input type="number" name="tempo_preparo_min" min="0"
                       value="{{ old('tempo_preparo_min', $ficha['tempo_preparo_min'] ?? '') }}">
            </label>
            <label>Rendimento (porções) *
                <input type="number" name="rendimento_porcoes" min="1" required
                       value="{{ old('rendimento_porcoes', $ficha['rendimento_porcoes'] ?? 50) }}">
            </label>
            <label>Peso por porção (g)
                <input type="number" step="0.01" name="peso_porcao_g" min="0"
                       value="{{ old('peso_porcao_g', $ficha['peso_porcao_g'] ?? '') }}">
            </label>
        </div>

        <h2 style="margin-top:18px"><i class="fa fa-list"></i> Ingredientes (catálogo PNAE)</h2>
        <p style="color:var(--suave);font-size:.88rem;margin:0 0 10px">
            Selecione itens do catálogo para calcular kcal, macros e custo automaticamente (5.2.7 / 5.2.9).
        </p>

        @php
            $ings = old('ingrediente_item_id')
                ? collect(old('ingrediente_item_id', []))->map(function ($id, $i) {
                    return [
                        'item_id' => (int) $id,
                        'nome' => old('ingrediente_nome.'.$i, ''),
                        'quantidade' => old('ingrediente_quantidade.'.$i, ''),
                        'unidade' => old('ingrediente_unidade.'.$i, 'g'),
                    ];
                })->all()
                : ($ficha['ingredientes'] ?? []);
            if ($ings === []) {
                $ings = [['item_id' => null, 'nome' => '', 'quantidade' => '', 'unidade' => 'g']];
            }
        @endphp

        <div id="ingredientes-lista">
            @foreach($ings as $idx => $ing)
                <div class="ingrediente-linha" data-idx="{{ $idx }}">
                    <select name="ingrediente_item_id[]" class="ing-item" onchange="preencherNome(this)">
                        <option value="">— item do catálogo —</option>
                        @foreach($itensCatalogo as $it)
                            <option value="{{ $it['id'] }}"
                                data-nome="{{ $it['nome'] }}"
                                data-unidade="{{ $it['unidade'] ?? 'g' }}"
                                @selected((int) ($ing['item_id'] ?? 0) === (int) $it['id'])>
                                {{ $it['nome'] }}
                                @if(!empty($it['kcal_porcao'])) ({{ number_format((float)$it['kcal_porcao'],0,',','.') }} kcal) @endif
                            </option>
                        @endforeach
                    </select>
                    <input type="text" name="ingrediente_nome[]" class="ing-nome"
                           value="{{ is_array($ing) ? ($ing['nome'] ?? '') : $ing }}"
                           placeholder="Nome (se sem catálogo)">
                    <input type="number" step="0.01" min="0" name="ingrediente_quantidade[]"
                           value="{{ is_array($ing) ? ($ing['quantidade'] ?? '') : '' }}"
                           placeholder="Qtd">
                    <select name="ingrediente_unidade[]">
                        @foreach(['g','kg','mL','L','un','porção'] as $u)
                            <option value="{{ $u }}" @selected((is_array($ing) ? ($ing['unidade'] ?? 'g') : 'g') === $u)>{{ $u }}</option>
                        @endforeach
                    </select>
                    <button type="button" class="botao claro" onclick="removerIngrediente(this)" title="Remover">
                        <i class="fa fa-trash"></i>
                    </button>
                </div>
            @endforeach
        </div>
        <button type="button" class="botao claro" onclick="adicionarIngrediente()" style="margin:8px 0 16px">
            <i class="fa fa-plus"></i> Adicionar ingrediente
        </button>

        @if(count($itensCatalogo) === 0)
            <p class="flash alerta">Catálogo de itens vazio — cadastre itens em <a href="{{ url('/alimentacao-escolar/itens') }}">Itens / PNAE</a> para nutrição automática.</p>
        @endif

        <h2><i class="fa fa-book"></i> Modo de preparo</h2>
        <div class="form-grid">
            <label class="col-wide">Instruções
                <textarea name="modo_preparo" rows="5" placeholder="Passo a passo do preparo…">{{ old('modo_preparo', $ficha['modo_preparo'] ?? '') }}</textarea>
            </label>
            <label class="col-wide">Utensílios
                <input type="text" name="utensilios"
                       value="{{ old('utensilios', $ficha['utensilios'] ?? '') }}"
                       placeholder="Ex: panelão, colher de pau, escumadeira">
            </label>
            <label>
                <input type="checkbox" name="ativo" value="1" @checked(old('ativo', $ficha['ativo'] ?? true))>
                Ficha ativa
            </label>
            <div class="acoes">
                <a class="botao claro" href="{{ url('/alimentacao-escolar/fichas-tecnicas') }}">Cancelar</a>
                <button type="submit" class="botao"><i class="fa fa-save"></i> Salvar ficha técnica</button>
            </div>
        </div>
    </form>
</section>

<style>
.ingrediente-linha {
    display: grid;
    grid-template-columns: 1.4fr 1.2fr 90px 90px 44px;
    gap: 8px;
    margin: 0 0 8px;
    align-items: center;
}
.ingrediente-linha select,
.ingrediente-linha input {
    min-height: 36px;
    padding: 6px 8px;
    border: 1px solid var(--linha);
    border-radius: 8px;
    font: inherit;
    background: #fff;
}
@media (max-width: 720px) {
    .ingrediente-linha { grid-template-columns: 1fr 1fr; }
}
</style>
<script>
const catalogoOptions = @json(collect($itensCatalogo)->map(fn ($it) => [
    'id' => $it['id'],
    'nome' => $it['nome'],
    'unidade' => $it['unidade'] ?? 'g',
    'kcal' => $it['kcal_porcao'] ?? null,
])->values());

function optionsHtml(selectedId) {
    let html = '<option value="">— item do catálogo —</option>';
    catalogoOptions.forEach(function (it) {
        const sel = String(selectedId || '') === String(it.id) ? ' selected' : '';
        const kcal = it.kcal ? ' (' + Math.round(it.kcal) + ' kcal)' : '';
        html += '<option value="' + it.id + '" data-nome="' + it.nome.replace(/"/g, '&quot;') + '" data-unidade="' + (it.unidade || 'g') + '"' + sel + '>' + it.nome + kcal + '</option>';
    });
    return html;
}

function preencherNome(sel) {
    const opt = sel.options[sel.selectedIndex];
    if (!opt || !opt.value) return;
    const linha = sel.closest('.ingrediente-linha');
    const nome = linha.querySelector('.ing-nome');
    const uni = linha.querySelector('select[name="ingrediente_unidade[]"]');
    if (nome && !nome.value) nome.value = opt.getAttribute('data-nome') || opt.textContent;
    if (uni && opt.getAttribute('data-unidade')) uni.value = opt.getAttribute('data-unidade');
}

function adicionarIngrediente() {
    const wrap = document.createElement('div');
    wrap.className = 'ingrediente-linha';
    wrap.innerHTML =
        '<select name="ingrediente_item_id[]" class="ing-item" onchange="preencherNome(this)">' + optionsHtml('') + '</select>' +
        '<input type="text" name="ingrediente_nome[]" class="ing-nome" placeholder="Nome (se sem catálogo)">' +
        '<input type="number" step="0.01" min="0" name="ingrediente_quantidade[]" placeholder="Qtd">' +
        '<select name="ingrediente_unidade[]"><option value="g">g</option><option value="kg">kg</option><option value="mL">mL</option><option value="L">L</option><option value="un">un</option><option value="porção">porção</option></select>' +
        '<button type="button" class="botao claro" onclick="removerIngrediente(this)"><i class="fa fa-trash"></i></button>';
    document.getElementById('ingredientes-lista').appendChild(wrap);
}

function removerIngrediente(btn) {
    const linhas = document.querySelectorAll('.ingrediente-linha');
    if (linhas.length > 1) btn.closest('.ingrediente-linha').remove();
    else alert('Mantenha ao menos um ingrediente.');
}
</script>
@endsection
