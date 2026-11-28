@php
    $turmasSelecionadas = $turmasSelecionadas ?? (($turma ?? '') !== '' ? [$turma] : []);
    $segmentosSelecionados = $segmentosSelecionados ?? (($segmento ?? '') !== '' ? [$segmento] : []);
@endphp
<form class="buriti-filtro" method="get" action="{{ url('/alimentacao-escolar/cardapio') }}">
    <input type="hidden" name="escopo" value="{{ $escopoAtual }}">
    <label>Data <input type="date" name="data" value="{{ $data }}"></label>
    @if($escopoAtual !== 'rede')
        <label>Escola
            <select name="escola">
                @foreach(($escolas ?? [['cod'=>null,'nome'=>'Rede']]) as $e)
                    <option value="{{ $e['nome'] }}" @selected(($escola ?? '') === $e['nome'])>{{ $e['nome'] }}</option>
                @endforeach
            </select>
        </label>
    @endif
    @if(in_array($escopoAtual, ['turma', 'escola'], true))
        <label class="col-wide">{{ $escopoAtual === 'turma' ? 'Turmas' : 'Turmas (opc.)' }}
            <select name="turmas[]" multiple size="{{ min(6, max(3, count($turmas ?? []) + 1)) }}" style="min-height:72px">
                @foreach(($turmas ?? []) as $t)
                    <option value="{{ (int) ($t['id'] ?? 0) }}::{{ $t['nome'] }}"
                        @selected(in_array($t['nome'], $turmasSelecionadas, true))>{{ $t['nome'] }}</option>
                @endforeach
            </select>
            <small style="color:var(--suave)">Ctrl/Cmd para marcar várias.</small>
        </label>
    @endif
    @if(in_array($escopoAtual, ['segmento', 'escola'], true))
        <label class="col-wide">Segmentos
            <select name="segmentos[]" multiple size="{{ min(6, max(3, count($segmentos ?? []))) }}" style="min-height:72px">
                @foreach(($segmentos ?? []) as $cod => $rotulo)
                    <option value="{{ $cod }}" @selected(in_array($cod, $segmentosSelecionados, true))>{{ $rotulo }}</option>
                @endforeach
            </select>
            <small style="color:var(--suave)">Ctrl/Cmd para marcar vários.</small>
        </label>
    @endif
    <div class="acoes">
        <button class="botao" type="submit"><i class="fa fa-search"></i> Aplicar</button>
    </div>
</form>
