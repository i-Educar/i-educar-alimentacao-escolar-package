@extends('alimentacao::layout')

@section('titulo', 'Restrições alimentares')
@section('migalha', 'Restrições')
@section('icone-tela', 'fa-medkit')
@section('titulo-tela', 'Cuidados alimentares')
@section('lead')
    Alunos com alergia, intolerância ou restrição — a cozinha precisa ver antes de servir
@endsection

@section('acoes-topo')
    <a class="botao icone" href="{{ url('/alimentacao-escolar/cardapio') }}" title="Cardápio" aria-label="Cardápio"><i aria-hidden="true" class="fa fa-cutlery"></i></a>
@endsection

@section('conteudo')

    @if(!empty($podeMontar))
        <div class="bloco">
            <h2><i class="fa fa-plus-circle"></i> Nova restrição</h2>
            <form class="form-grid" method="post" action="{{ url('/alimentacao-escolar/restricoes') }}" enctype="multipart/form-data">
                @csrf
                <label class="col-wide">Aluno
                    <div class="busca-select" data-busca-select>
                        <input type="search" class="busca-select-input" placeholder="Buscar por nome ou código…" autocomplete="off">
                        <input type="hidden" name="aluno_id" value="">
                        <ul class="busca-select-lista">
                            @forelse(($alunos ?? []) as $a)
                                <li data-id="{{ $a['id'] }}"
                                    data-label="{{ $a['nome'] }} (#{{ $a['id'] }})"
                                    data-texto="{{ mb_strtolower($a['nome'].' '.$a['id']) }}">
                                    {{ $a['nome'] }} <em>#{{ $a['id'] }}</em>
                                </li>
                            @empty
                                <li class="vazio">Nenhum aluno encontrado no núcleo. Digite o código manualmente abaixo.</li>
                            @endforelse
                            <li class="vazio" style="display:none">Nenhum resultado para a busca.</li>
                        </ul>
                    </div>
                </label>
                @if(empty($alunos))
                    <label>Código do aluno <input type="number" name="aluno_id_manual" min="1" placeholder="Ex.: 1001" oninput="this.form.aluno_id.value=this.value"></label>
                @endif
                <label class="col-wide">Restrição
                    <input name="restricao" required placeholder="Ex.: intolerância a lactose">
                </label>
                <label class="col-wide">Observação / conduta <input name="observacao" placeholder="Ex.: substituto sugerido pela nutrição"></label>
                @include('buriti-ui::campo-anexo', [
                    'campo' => 'laudo',
                    'rotulo' => 'Laudo médico ou nutricional',
                    'tituloAcao' => 'Soltar o laudo aqui',
                    'ajuda' => 'PDF ou foto do papel — como anexar no WhatsApp.',
                    'accept' => 'application/pdf,.pdf,image/jpeg,image/png,.jpg,.jpeg,.png',
                    'icone' => 'fa-file-text-o',
                ])
                <div class="acoes">
                    <button class="botao" type="submit"><i class="fa fa-save"></i> Registrar restrição</button>
                </div>
            </form>
        </div>
    @endif

    <div class="numeros">
        <article style="--tom: var(--ambar)">
            <div class="kpi-ico"><i class="fa fa-medkit"></i></div>
            <span>Registros</span>
            <strong>{{ count($linhas) }}</strong>
        </article>
        <article style="--tom: var(--erro)">
            <div class="kpi-ico"><i class="fa fa-exclamation-triangle"></i></div>
            <span>Atenção</span>
            <strong>{{ count($linhas) > 0 ? 'Ativo' : '—' }}</strong>
        </article>
    </div>

    @if(count($linhas) === 0)
        <div class="bloco cena-vazia">
            <i class="fa fa-heartbeat"></i>
            <p>Nenhuma restrição. Cadastre um aluno acima ou rode a demo PoC.</p>
        </div>
    @else
        <div class="mapa-rede">
            @foreach($linhas as $i => $l)
                @php
                    $cod = (int) ($l->ref_cod_aluno ?? $l->aluno_id ?? 0);
                    $nome = $nomes[$cod] ?? ('Aluno #'.$cod);
                @endphp
                <article class="escola-card" style="animation-delay: {{ $i * 0.05 }}s;border-top-color:var(--ambar)">
                    <header>
                        <span class="predio" style="background:linear-gradient(145deg,#d4a017,var(--ambar))"><i class="fa fa-user"></i></span>
                        <div>
                            <h3>{{ $nome }}</h3>
                            <p class="qtd">#{{ $cod }} · {{ $l->updated_at ?? '—' }}</p>
                        </div>
                    </header>
                    <div class="alerta-card" style="margin:0">
                        <span class="selo"><i class="fa fa-ban"></i></span>
                        <div>
                            <strong>Restrição</strong>
                            <div>{{ $l->restricao }}</div>
                            @if(!empty($l->observacao))
                                <div style="margin-top:4px;color:var(--suave);font-size:.85rem">{{ $l->observacao }}</div>
                            @endif
                            @if(!empty($l->laudo_path))
                                <div style="margin-top:6px">
                                    <a class="botao claro" href="{{ url('/alimentacao-escolar/restricoes/'.($l->id).'/laudo') }}" style="padding:4px 8px;font-size:.78rem">
                                        <i class="fa fa-file-pdf-o"></i> {{ $l->laudo_nome ?? 'Laudo' }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
    @endif
@endsection
