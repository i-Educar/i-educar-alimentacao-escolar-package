@extends('alimentacao::layout')

@section('titulo', 'Configurações')
@section('migalha', 'Configurações')
@section('icone-tela', 'fa-sliders')
@section('titulo-tela', 'Configurações da alimentação escolar')
@section('lead', 'Automações, janelas de refeição, responsável técnico — apoio à decisão, gestão e auditoria')

@section('acoes-topo')
    <a class="botao claro icone" href="{{ url('/alimentacao-escolar') }}" title="Painel" aria-label="Painel"><i aria-hidden="true" class="fa fa-th-large"></i></a>
@endsection

@section('conteudo')
@php $cfg = $cfg ?? []; $rt = $rt ?? null; @endphp

@unless($podeConfigurar)
    <p class="flash alerta">Visualização — sem permissão para alterar. Peça acesso SME/admin.</p>
@endunless

<form method="post" action="{{ url('/alimentacao-escolar/configuracoes') }}">
    @csrf
    <fieldset style="border:0;margin:0;padding:0" @if(! $podeConfigurar) disabled @endif>
    <div class="bloco">
        <h2><i class="fa fa-cogs"></i> Automações e operação</h2>
        <div class="form-grid">
            <label><span>Permitir montagem de cardápio</span>
                <select name="montar_cardapio">
                    <option value="1" @selected(!empty($cfg['montar_cardapio']))>Sim</option>
                    <option value="0" @selected(isset($cfg['montar_cardapio']) && ! $cfg['montar_cardapio'])>Não</option>
                </select>
            </label>
            <label><span>Bandejas automáticas ao lançar vazio</span>
                <select name="bandejas_automaticas">
                    <option value="1" @selected(!empty($cfg['bandejas_automaticas']))>Sim</option>
                    <option value="0" @selected(isset($cfg['bandejas_automaticas']) && ! $cfg['bandejas_automaticas'])>Não</option>
                </select>
            </label>
            <label><span>Estimar porções ao lançar cardápio</span>
                <select name="estimar_porcoes_ao_lancar">
                    <option value="1" @selected(!empty($cfg['estimar_porcoes_ao_lancar']))>Sim</option>
                    <option value="0" @selected(isset($cfg['estimar_porcoes_ao_lancar']) && ! $cfg['estimar_porcoes_ao_lancar'])>Não</option>
                </select>
            </label>
            <label><span>Unidade só registra fatos (servidas)</span>
                <select name="unidade_so_registra_fatos">
                    <option value="1" @selected(!empty($cfg['unidade_so_registra_fatos']))>Sim</option>
                    <option value="0" @selected(isset($cfg['unidade_so_registra_fatos']) && ! $cfg['unidade_so_registra_fatos'])>Não</option>
                </select>
            </label>
            <label><span>Alertar cardápio fora do horário da turma</span>
                <select name="alerta_horario_turma">
                    <option value="1" @selected(!empty($cfg['alerta_horario_turma']))>Sim</option>
                    <option value="0" @selected(isset($cfg['alerta_horario_turma']) && ! $cfg['alerta_horario_turma'])>Não</option>
                </select>
            </label>
            <label>Porções padrão (fallback)
                <input type="number" min="1" name="porcoes_padrao" value="{{ (int) ($cfg['porcoes_padrao'] ?? 100) }}">
            </label>
            <label>Lanches por dia (1–4)
                <input type="number" min="1" max="4" name="lanches_por_dia" value="{{ (int) ($cfg['lanches_por_dia'] ?? 2) }}">
            </label>
            <label><span>Incluir jantar nos slots</span>
                <select name="incluir_jantar">
                    <option value="0" @selected(! in_array('jantar', $cfg['slots_refeicao'] ?? [], true))>Não</option>
                    <option value="1" @selected(in_array('jantar', $cfg['slots_refeicao'] ?? [], true))>Sim</option>
                </select>
            </label>
        </div>
    </div>

    <div class="bloco">
        <h2><i class="fa fa-clock-o"></i> Janelas de refeição (validação de horário)</h2>
        <div class="form-grid">
            @foreach(['cafe'=>'Café','almoco'=>'Almoço','lanche'=>'Lanche','lanche_2'=>'Lanche 2','jantar'=>'Jantar'] as $cod => $rot)
                @php $j = $cfg['janelas'][$cod] ?? ['inicio'=>'08:00','fim'=>'09:00']; @endphp
                <label>{{ $rot }} · início
                    <input type="time" name="janela_{{ $cod }}_inicio" value="{{ $j['inicio'] ?? '08:00' }}">
                </label>
                <label>{{ $rot }} · fim
                    <input type="time" name="janela_{{ $cod }}_fim" value="{{ $j['fim'] ?? '09:00' }}">
                </label>
            @endforeach
        </div>
    </div>

    <div class="bloco">
        <h2><i class="fa fa-user-md"></i> Responsável técnico (assinatura nos PDFs)</h2>
        <div class="form-grid">
            <label>Nome
                <input name="rt_nome" value="{{ $rt['nome'] ?? ($cfg['rt_nome'] ?? '') }}" required maxlength="160"
                       placeholder="Nome completo do nutricionista">
            </label>
            <label>Registro (CRN)
                <input name="rt_registro" value="{{ $rt['registro'] ?? ($cfg['rt_registro'] ?? '') }}" maxlength="80"
                       placeholder="ex.: CRN-3 12345">
            </label>
            <label>Cargo
                <input name="rt_cargo" value="{{ $rt['cargo'] ?? ($cfg['rt_cargo'] ?? 'Nutricionista responsável técnico') }}" maxlength="120">
            </label>
            <label>E-mail
                <input type="email" name="rt_email" value="" maxlength="160" placeholder="opcional">
            </label>
        </div>
    </div>

    @if($podeConfigurar)
        <div class="acoes" style="display:flex;justify-content:flex-end;margin:8px 0 24px">
            <button class="botao" type="submit"><i class="fa fa-save"></i> Salvar configurações</button>
        </div>
    @endif
    </fieldset>
</form>

<div class="bloco">
    <h2><i class="fa fa-info-circle"></i> Papel do módulo</h2>
    <p style="margin:0;color:var(--suave);line-height:1.5">
        Ferramenta de <strong>apoio</strong> (estimativas e catálogo), <strong>decisão</strong> (PNAE, horário, balanço),
        <strong>gestão</strong> (cardápio, porções, RT) e <strong>auditoria</strong> (histórico Laguna).
        Ajuste as automações acima conforme a realidade da rede.
    </p>
</div>
@endsection
