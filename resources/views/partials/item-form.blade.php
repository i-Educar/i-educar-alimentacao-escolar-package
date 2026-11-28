@php
    $item = $item ?? [];
    $id = (int) ($item['id'] ?? 0);
    $sel = static function (string $campo, string $valor) use ($item): string {
        return ((string) ($item[$campo] ?? '')) === $valor ? 'selected' : '';
    };
    $val = static function (string $campo, $padrao = '') use ($item) {
        $v = $item[$campo] ?? null;
        return $v === null || $v === '' ? $padrao : $v;
    };
@endphp
<form class="form-grid item-form-merenda" method="post" action="{{ url('/alimentacao-escolar/itens') }}">
    @csrf
    <input type="hidden" name="id" value="{{ $id }}">

    <p class="fluxo-sep" style="grid-column:1/-1;margin:0">Identificação</p>
    <label>Nome
        <input name="nome" required maxlength="160" value="{{ $val('nome') }}" placeholder="ex.: feijão">
    </label>
    <label>Categoria
        <select name="categoria">
            <option value="in_natura" {{ $sel('categoria', 'in_natura') ?: ($id === 0 ? 'selected' : '') }}>In natura</option>
            <option value="minimamente" {{ $sel('categoria', 'minimamente') }}>Minimamente processado</option>
            <option value="processado" {{ $sel('categoria', 'processado') }}>Processado</option>
            <option value="ultraprocessado" {{ $sel('categoria', 'ultraprocessado') }}>Ultraprocessado</option>
        </select>
    </label>
    <label>Grupo PNAE
        <select name="grupo_pnae">
            @foreach(['cereal'=>'Cereal','leguminosa'=>'Leguminosa','hortalica'=>'Hortaliça','fruta'=>'Fruta','proteina'=>'Proteína','laticinio'=>'Laticínio','outro'=>'Outro'] as $cod => $rot)
                <option value="{{ $cod }}" {{ $sel('grupo_pnae', $cod) ?: ($id === 0 && $cod === 'outro' ? 'selected' : '') }}>{{ $rot }}</option>
            @endforeach
        </select>
    </label>
    <label>Unidade <input name="unidade" value="{{ $val('unidade', 'porção') }}"></label>

    <p class="fluxo-sep" style="grid-column:1/-1;margin:4px 0 0">Refeições do dia em que o item pode ser usado</p>
    <div class="col-wide" style="display:flex;flex-wrap:wrap;gap:10px 14px;padding:4px 0 8px">
        @php
            $refsItem = $item['refeicoes'] ?? ['cafe','almoco','lanche'];
            $opcoesRef = [
                'cafe' => 'Café',
                'almoco' => 'Almoço',
                'lanche' => 'Lanche (repetível no dia)',
                'jantar' => 'Jantar',
            ];
        @endphp
        @foreach($opcoesRef as $cod => $rot)
            <label style="display:inline-flex;align-items:center;gap:6px;font-weight:700;margin:0;color:var(--tinta)">
                <input type="checkbox" name="refeicoes[]" value="{{ $cod }}"
                    @checked(in_array($cod, $refsItem, true) || ($cod === 'lanche' && collect($refsItem)->contains(fn ($r) => str_starts_with((string)$r, 'lanche'))))>
                {{ $rot }}
            </label>
        @endforeach
        <small style="width:100%;font-weight:500;color:var(--suave)">Marque Lanche para liberar o item em todos os intervalos (lanche 1, 2…).</small>
    </div>

    <p class="fluxo-sep" style="grid-column:1/-1;margin:4px 0 0">Para balanço e compras (obrigatório na prática)</p>
    <label>Peso da porção (g)
        <input type="number" step="0.01" min="0" name="peso_porcao_g" value="{{ $val('peso_porcao_g') }}" placeholder="ex.: 100" required>
        <small style="font-weight:500;color:var(--suave)">Peso médio da porção servida ao estudante</small>
    </label>
    <label>kcal / porção
        <input type="number" step="0.1" min="0" name="kcal_porcao" value="{{ $val('kcal_porcao') }}" placeholder="ex.: 130" required>
        <small style="font-weight:500;color:var(--suave)">Energia da mesma porção</small>
    </label>
    <label>Custo unitário (R$)
        <input type="number" step="0.0001" min="0" name="custo_unitario" value="{{ $val('custo_unitario') }}" placeholder="ex.: 0,55" required>
        <small style="font-weight:500;color:var(--suave)">Custo médio por porção — base da previsão de compra</small>
    </label>

    <details class="col-wide" style="margin:4px 0;border:1px dashed var(--linha);border-radius:10px;padding:8px 12px;background:color-mix(in srgb,var(--clara) 40%,#fff)">
        <summary style="cursor:pointer;font-weight:700;color:var(--suave);font-size:.82rem">Macronutrientes (opcional)</summary>
        <div class="form-grid" style="margin-top:10px">
            <label>Proteína (g) <input type="number" step="0.1" min="0" name="proteina_g" value="{{ $val('proteina_g') }}"></label>
            <label>Carboidrato (g) <input type="number" step="0.1" min="0" name="carboidrato_g" value="{{ $val('carboidrato_g') }}"></label>
            <label>Lipídio (g) <input type="number" step="0.1" min="0" name="lipidio_g" value="{{ $val('lipidio_g') }}"></label>
            <label>Fibra (g) <input type="number" step="0.1" min="0" name="fibra_g" value="{{ $val('fibra_g') }}"></label>
            <label>Sódio (mg) <input type="number" step="0.1" min="0" name="sodio_mg" value="{{ $val('sodio_mg') }}"></label>
        </div>
    </details>

    <label><span>Agricultura familiar</span>
        <select name="origem_af">
            <option value="0" @selected(!($item['origem_af'] ?? false))>Não</option>
            <option value="1" @selected(!empty($item['origem_af']))>Sim</option>
        </select>
    </label>
    <label><span>Padrão PNAE</span>
        <select name="padrao_pnae">
            <option value="1" @selected(($item['padrao_pnae'] ?? true))>Sim</option>
            <option value="0" @selected(isset($item['padrao_pnae']) && ! $item['padrao_pnae'])>Não (fora do padrão)</option>
        </select>
    </label>
    <label><span>Ativo</span>
        <select name="ativo">
            <option value="1" @selected(($item['ativo'] ?? true))>Sim</option>
            <option value="0" @selected(isset($item['ativo']) && ! $item['ativo'])>Não</option>
        </select>
    </label>

    <div class="acoes">
        <button class="botao" type="submit">
            <i class="fa fa-save"></i> {{ $id > 0 ? 'Salvar alterações' : 'Cadastrar item' }}
        </button>
    </div>
</form>
