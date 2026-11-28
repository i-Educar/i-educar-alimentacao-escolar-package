@extends('alimentacao::layout')

@section('titulo', 'Porções servidas')
@section('migalha', 'Perfil / porções')
@section('icone-tela', 'fa-building')
@section('titulo-tela', 'Porções — estimativa e fatos')
@section('lead', 'O sistema estima as quantidades; a unidade registra o que foi servido. Uma porção pode atender vários segmentos.')

@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/balanco') }}" title="Balanço" aria-label="Balanço"><i aria-hidden="true" class="fa fa-balance-scale"></i></a>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/cardapio') }}" title="Cardápio" aria-label="Cardápio"><i aria-hidden="true" class="fa fa-cutlery"></i></a>
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar/configuracoes') }}" title="Config" aria-label="Config"><i aria-hidden="true" class="fa fa-sliders"></i></a>
@endsection

@section('conteudo')
<style>
    .segs-check { display:flex; flex-wrap:wrap; gap:8px 12px; }
    .segs-check label { display:inline-flex; align-items:center; gap:6px; font-weight:700; margin:0; color:var(--tinta); font-size:.85rem; }
</style>

<form class="buriti-filtro" method="get" action="{{ url('/alimentacao-escolar/perfil') }}">
    <label>Data <input type="date" name="data" value="{{ $data }}"></label>
    <label>Escola
        <select name="escola">
            <option value="">Todas</option>
            @foreach(($escolas ?? []) as $e)
                <option value="{{ $e['nome'] }}" @selected(($escola ?? '') === $e['nome'])>{{ $e['nome'] }}</option>
            @endforeach
        </select>
    </label>
    <div class="acoes">
        <button class="botao" type="submit"><i class="fa fa-filter"></i> Filtrar</button>
    </div>
</form>

@if($podeMontar)
<div class="bloco">
    <h2><i class="fa fa-check-square-o"></i> Registrar fato do serviço</h2>
    <p style="margin:0 0 12px;color:var(--suave);font-size:.85rem;line-height:1.45">
        @if(!empty($soFatos))
            Seu perfil registra <strong>apenas porções servidas</strong>. As previstas vêm da estimativa automática (matrículas / cardápio).
        @else
            SME pode ajustar previstas; a unidade normalmente só confirma o servido.
        @endif
        Selecione um ou mais <strong>segmentos</strong> atendidos pela mesma porção.
    </p>
    <form class="form-grid" method="post" action="{{ url('/alimentacao-escolar/perfil') }}">
        @csrf
        <label>Data <input type="date" name="data" value="{{ $data }}" required></label>
        <label>Escola
            <select name="escola" required>
                @foreach(($escolas ?? []) as $e)
                    @if(($e['nome'] ?? '') !== 'Rede')
                        <option value="{{ $e['nome'] }}" @selected(($escola ?? '') === $e['nome'])>{{ $e['nome'] }}</option>
                    @endif
                @endforeach
            </select>
        </label>
        <label>Refeição
            <select name="refeicao">
                @foreach(($slots ?? ['almoco'=>['rotulo'=>'Almoço']]) as $cod => $meta)
                    <option value="{{ $cod }}" @selected($cod === 'almoco')>{{ $meta['rotulo'] ?? $cod }}</option>
                @endforeach
            </select>
        </label>
        <label>Turma (opcional) <input name="turma" placeholder="ex.: 5º A"></label>

        @unless(!empty($soFatos))
            <label>Porções previstas
                <input type="number" min="0" name="porcoes_previstas" value="0" placeholder="0 = estimar">
                <small style="font-weight:500;color:var(--suave)">Vazio/0 estima pela matrícula</small>
            </label>
        @endunless

        <label>Porções servidas <input type="number" min="0" name="porcoes_servidas" value="0" required></label>

        <div class="col-wide">
            <span style="display:block;font-size:.75rem;font-weight:700;color:var(--suave);margin:0 0 6px">Segmentos atendidos por esta porção</span>
            <div class="segs-check">
                @foreach(($segmentos ?? []) as $cod => $rotulo)
                    <label>
                        <input type="checkbox" name="segmentos[]" value="{{ $cod }}">
                        {{ $rotulo }}
                    </label>
                @endforeach
            </div>
        </div>

        <label class="col-wide">Observação <input name="observacao" placeholder="Sobra, falta, substituição…"></label>
        <label class="col-wide" style="display:flex;align-items:center;gap:8px;font-weight:700">
            <input type="checkbox" name="confirmar" value="1" checked> Confirmar registro do serviço
        </label>
        <div class="acoes">
            <button class="botao" type="submit"><i class="fa fa-check"></i> Registrar fato</button>
        </div>
    </form>
</div>
@endif

<div class="bloco">
    <h2><i class="fa fa-list"></i> Serviços do dia ({{ count($servicos ?? []) }})</h2>
    <div style="overflow:auto">
        <table>
            <thead>
                <tr>
                    <th>Escola</th>
                    <th>Refeição</th>
                    <th>Segmentos</th>
                    <th>Turma</th>
                    <th>Previstas</th>
                    <th>Servidas</th>
                    <th>Δ</th>
                    <th>Origem</th>
                    <th>Confirmação</th>
                </tr>
            </thead>
            <tbody>
                @forelse(($servicos ?? []) as $s)
                    @php
                        $delta = (int)$s['porcoes_servidas'] - (int)$s['porcoes_previstas'];
                        $segs = $s['segmentos'] ?? [];
                        $segsRot = collect($segs)->map(fn ($c) => ($segmentos[$c] ?? $c))->implode(', ');
                    @endphp
                    <tr>
                        <td>{{ $s['escola'] }}</td>
                        <td>{{ $slots[$s['refeicao']]['rotulo'] ?? $s['refeicao'] }}</td>
                        <td>{{ $segsRot !== '' ? $segsRot : '—' }}</td>
                        <td>{{ $s['turma'] ?: '—' }}</td>
                        <td>
                            {{ $s['porcoes_previstas'] }}
                            @if(!empty($s['estimado'])) <span class="badge warn">est.</span> @endif
                        </td>
                        <td>{{ $s['porcoes_servidas'] }}</td>
                        <td>
                            <span class="badge {{ abs($delta) >= max(1, (int)round($s['porcoes_previstas']*0.2)) ? 'fail' : 'ok' }}">
                                {{ $delta >= 0 ? '+'.$delta : $delta }}
                            </span>
                        </td>
                        <td>{{ $s['origem'] ?: '—' }}</td>
                        <td>
                            @if($s['confirmado_em'])
                                <span class="badge ok">{{ \Illuminate\Support\Str::of($s['confirmado_em'])->substr(0,16) }}</span>
                            @else
                                <span class="badge warn">pendente</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9">Nenhum serviço nesta data/filtro. Lance o cardápio para gerar estimativas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
