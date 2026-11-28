<form class="buriti-filtro" method="get" action="{{ url('/alimentacao-escolar/balanco') }}">
    <label>Início <input type="date" name="inicio" value="{{ $inicio }}"></label>
    <label>Fim <input type="date" name="fim" value="{{ $fim }}"></label>
    <label>Escola
        <select name="escola">
            <option value="">Rede inteira</option>
            @foreach(($escolas ?? []) as $e)
                <option value="{{ $e['nome'] }}" @selected(($escola ?? '') === $e['nome'])>{{ $e['nome'] }}</option>
            @endforeach
        </select>
    </label>
    <div class="acoes">
        <button class="botao" type="submit"><i class="fa fa-filter"></i> Aplicar</button>
    </div>
</form>
