<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Http\Controllers;

use iEducar\Packages\BuritiAlimentacaoEscolar\Services\BalancoAlimentacao;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\BandejasAutomaticas;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\CardapioDia;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\CardapioLote;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\CatalogoItens;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\ConfigAlimentacao;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\EscopoCardapio;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\FichaTecnica;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\HorarioCardapio;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\MapaDistribuicao;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\PerfilEscolar;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\PnaeRegras;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\RestricaoAlimentar;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\SobrasRegistro;
use iEducar\Packages\BuritiAlimentacaoEscolar\Support\PermissoesAlimentacao;
use Illuminate\Http\JsonResponse;
use iEducar\Packages\BuritiLagunaAmbiente\Support\AnexoFluxo;
use iEducar\Packages\BuritiLagunaAmbiente\Support\AuditoriaFluxo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class CardapioController
{
    public function index(): View
    {
        $temSchema = Schema::hasTable('alimentacao.cardapio_dia');
        $totalCardapios = $temSchema ? (int) DB::table('alimentacao.cardapio_dia')->count() : 0;
        $totalRestricoes = Schema::hasTable('alimentacao.restricao_aluno')
            ? (int) DB::table('alimentacao.restricao_aluno')->count()
            : 0;
        $totalItens = Schema::hasTable('alimentacao.item')
            ? (int) DB::table('alimentacao.item')->where('ativo', true)->count()
            : 0;
        $itensAf = 0;
        if (Schema::hasTable('alimentacao.item')) {
            $colAf = Schema::hasColumn('alimentacao.item', 'origem_af')
                ? 'origem_af'
                : (Schema::hasColumn('alimentacao.item', 'agricultura_familiar') ? 'agricultura_familiar' : null);
            if ($colAf !== null) {
                $itensAf = (int) DB::table('alimentacao.item')->where('ativo', true)->where($colAf, true)->count();
            }
        }
        $hoje = date('Y-m-d');
        $cardapiosHoje = $temSchema
            ? (int) DB::table('alimentacao.cardapio_dia')->where('data', $hoje)->count()
            : 0;
        $escolasCardapio = $temSchema
            ? (int) DB::table('alimentacao.cardapio_dia')->distinct('escola')->count('escola')
            : 0;

        $analise = [];
        if ($temSchema) {
            $recentes = DB::table('alimentacao.cardapio_dia')->orderByDesc('data')->orderByDesc('id')->limit(6)->get();
            foreach ($recentes as $row) {
                $analise[] = [
                    'titulo' => (string) $row->escola,
                    'meta' => 'Cardápio · '.$row->data.(! empty($row->turma) ? ' · '.$row->turma : ''),
                    'badge' => $row->data === $hoje ? 'Hoje' : 'Programado',
                    'badge_tom' => $row->data === $hoje ? 'ok' : 'warn',
                    'url' => '/alimentacao-escolar/cardapio?data='.$row->data.'&escola='.urlencode((string) $row->escola),
                ];
            }
        }
        if (Schema::hasTable('alimentacao.restricao_aluno')) {
            $restricoes = DB::table('alimentacao.restricao_aluno')->orderByDesc('id')->limit(5)->get();
            foreach ($restricoes as $r) {
                $analise[] = [
                    'titulo' => 'Restrição aluno #'.($r->ref_cod_aluno ?? $r->id),
                    'meta' => (string) ($r->descricao ?? $r->alimento ?? $r->tipo ?? 'Cuidado alimentar'),
                    'badge' => 'Laudo',
                    'badge_tom' => 'fail',
                    'url' => '/alimentacao-escolar/restricoes',
                ];
            }
        }
        if ($analise === []) {
            $analise[] = [
                'titulo' => $temSchema ? 'Sem cardápios ainda' : 'Schema pendente',
                'meta' => $temSchema ? 'Monte o cardápio do dia ou em lote' : 'Rode php artisan migrate',
                'badge' => $temSchema ? 'Vazio' : 'Migrar',
                'badge_tom' => 'warn',
                'url' => '/alimentacao-escolar/cardapio',
            ];
        }

        return view('alimentacao::painel', [
            'temSchema' => $temSchema,
            'kpis' => [
                ['rotulo' => 'Cardápios', 'valor' => $totalCardapios, 'icone' => 'fa-book', 'tom' => 'var(--almoco, var(--cor))'],
                ['rotulo' => 'Hoje', 'valor' => $cardapiosHoje, 'icone' => 'fa-sun-o', 'tom' => 'var(--lanche, var(--destaque))'],
                ['rotulo' => 'Escolas', 'valor' => $escolasCardapio, 'icone' => 'fa-building', 'tom' => '#0e7490'],
                ['rotulo' => 'Restrições', 'valor' => $totalRestricoes, 'icone' => 'fa-medkit', 'tom' => $totalRestricoes ? 'var(--ambar)' : 'var(--ok)'],
                ['rotulo' => 'Itens PNAE', 'valor' => $totalItens, 'icone' => 'fa-leaf', 'tom' => 'var(--ok)'],
                ['rotulo' => 'Agric. familiar', 'valor' => $itensAf, 'icone' => 'fa-pagelines', 'tom' => 'var(--cafe, var(--destaque))'],
            ],
            'destaques' => [
                [
                    'titulo' => $temSchema ? 'Merenda operacional' : 'Migração necessária',
                    'meta' => $cardapiosHoje.' cardápio(s) para hoje',
                    'icone' => $temSchema ? 'fa-cutlery' : 'fa-database',
                    'tom' => $temSchema ? 'var(--cor)' : 'var(--erro)',
                    'corpo' => $totalRestricoes
                        ? $totalRestricoes.' restrição(ões) ativas — confira antes de servir.'
                        : 'Nenhuma restrição cadastrada. '.$totalItens.' itens no catálogo PNAE.',
                ],
            ],
            'analise' => $analise,
            'analiseTitulo' => 'Cardápios recentes e restrições',
            'resumo' => 'Alimentação escolar · cardápio diário/lote, catálogo PNAE, restrições com laudo e mapa de distribuição.',
            'atalhos' => [
                ['url' => '/alimentacao-escolar/cardapio', 'icone' => 'fa-cutlery', 'rotulo' => 'Cardápio do dia', 'meta' => 'Montar bandejas'],
                ['url' => '/alimentacao-escolar/cardapio-lote', 'icone' => 'fa-calendar', 'rotulo' => 'Cardápio em lote', 'meta' => 'Várias escolas/datas'],
                ['url' => '/alimentacao-escolar/itens', 'icone' => 'fa-leaf', 'rotulo' => 'Itens / PNAE', 'meta' => 'Catálogo e AF'],
                ['url' => '/alimentacao-escolar/fichas-tecnicas', 'icone' => 'fa-file-text-o', 'rotulo' => 'Fichas Técnicas', 'meta' => 'Preparações'],
                ['url' => '/alimentacao-escolar/restricoes', 'icone' => 'fa-medkit', 'rotulo' => 'Restrições', 'meta' => 'Laudos'],
                ['url' => '/alimentacao-escolar/mapa', 'icone' => 'fa-map-o', 'rotulo' => 'Mapa', 'meta' => 'Distribuição'],
                ['url' => '/alimentacao-escolar/perfil', 'icone' => 'fa-building', 'rotulo' => 'Porções', 'meta' => 'Registrar fatos'],
                ['url' => '/alimentacao-escolar/balanco', 'icone' => 'fa-balance-scale', 'rotulo' => 'Balanço', 'meta' => 'Adesão e custos'],
                ['url' => '/alimentacao-escolar/configuracoes', 'icone' => 'fa-sliders', 'rotulo' => 'Configurações', 'meta' => 'Automações e RT'],
                ['url' => '/alimentacao-escolar/historico', 'icone' => 'fa-history', 'rotulo' => 'Auditoria', 'meta' => 'Histórico'],
            ],
            'fluxoTitulo' => 'Operação da merenda',
        ]);
    }

    public function cardapio(Request $request): View
    {
        $data = (string) $request->query('data', date('Y-m-d'));
        $escopo = EscopoCardapio::normalizar((string) $request->query('escopo', EscopoCardapio::ESCOLA));
        $escola = (string) $request->query('escola', $escopo === EscopoCardapio::REDE ? 'Rede' : 'Rede');
        $turmasSel = EscopoCardapio::normalizarTurmas(
            $request->query('turmas', []),
            $request->query('turma', ''),
            $request->query('turma_id', 0)
        );
        $turma = $turmasSel[0]['nome'] ?? '';
        $turmaId = (int) ($turmasSel[0]['id'] ?? 0);
        $segmentosSel = EscopoCardapio::normalizarSegmentos(
            $request->query('segmentos', []),
            $request->query('segmento')
        );
        $segmento = $segmentosSel[0] ?? '';
        $cardapio = null;
        $fonte = 'demo';
        $restricoes = [];
        $confirmadoEm = null;
        $cardapioId = null;

        if (Schema::hasTable('alimentacao.cardapio_dia')) {
            $q = DB::table('alimentacao.cardapio_dia')->where('data', $data);
            if (Schema::hasColumn('alimentacao.cardapio_dia', 'escopo')) {
                $q->where('escopo', $escopo);
            }
            if ($escopo === EscopoCardapio::REDE) {
                $q->where('escola', 'Rede');
            } else {
                $q->where('escola', $escola);
            }
            if ($escopo === EscopoCardapio::SEGMENTO && Schema::hasColumn('alimentacao.cardapio_dia', 'segmento')) {
                $q->where('segmento', $segmento !== '' ? $segmento : null);
            } elseif (Schema::hasColumn('alimentacao.cardapio_dia', 'segmento') && $segmento !== '') {
                $q->where('segmento', $segmento);
            }
            if ($turma !== '' && Schema::hasColumn('alimentacao.cardapio_dia', 'turma')) {
                $q->where('turma', $turma);
            } elseif (Schema::hasColumn('alimentacao.cardapio_dia', 'turma') && $escopo !== EscopoCardapio::TURMA) {
                $q->where(function ($w) {
                    $w->whereNull('turma')->orWhere('turma', '');
                });
            }
            $row = $q->first()
                ?? DB::table('alimentacao.cardapio_dia')->where('data', $data)->where('escola', $escola)->orderBy('id')->first()
                ?? DB::table('alimentacao.cardapio_dia')->where('data', $data)->orderBy('id')->first();

            if ($row !== null) {
                $refeicoes = is_string($row->refeicoes)
                    ? (json_decode($row->refeicoes, true) ?: [])
                    : (array) $row->refeicoes;
                $cardapio = new CardapioDia((string) $row->data, $refeicoes, (string) $row->escola);
                $fonte = 'alimentacao.cardapio_dia';
                $cardapioId = (int) $row->id;
                $confirmadoEm = $row->confirmado_em ?? null;
                if (! empty($row->turma)) {
                    $turma = (string) $row->turma;
                }
                if (! empty($row->segmento ?? null)) {
                    $segmento = (string) $row->segmento;
                }
                if (! empty($row->escopo ?? null)) {
                    $escopo = EscopoCardapio::normalizar((string) $row->escopo);
                }
            }
        }

        $catalogo = CatalogoItens::listar();
        $sugestaoVals = BandejasAutomaticas::valoresFormulario($catalogo);
        if ($cardapio === null) {
            $cardapio = new CardapioDia($data, [], $escola);
            $fonte = 'vazio';
        }

        if (Schema::hasTable('alimentacao.restricao_aluno')) {
            $rq = DB::table('alimentacao.restricao_aluno')->orderBy('ref_cod_aluno')->limit(200);
            if ($turmaId > 0 && Schema::hasColumn('alimentacao.restricao_aluno', 'turma_id')) {
                $rq->where(function ($w) use ($turmaId) {
                    $w->where('turma_id', $turmaId)->orWhereNull('turma_id');
                });
            }
            $restricoes = $rq->get()->map(static fn ($r): array => [
                'aluno_id' => (int) ($r->ref_cod_aluno ?? 0),
                'restricao' => (string) $r->restricao,
                'turma_id' => isset($r->turma_id) ? (int) $r->turma_id : null,
            ])->all();
        }

        $itens = [];
        foreach ($cardapio->toArray()['refeicoes'] ?? [] as $ref) {
            $itens = array_merge($itens, $ref['itens'] ?? []);
        }

        $nomes = $this->nomesAlunos(array_column($restricoes, 'aluno_id'));
        $alertas = [];
        foreach ($restricoes as $r) {
            $svc = new RestricaoAlimentar((int) $r['aluno_id'], [(string) $r['restricao']]);
            $conflitos = $svc->itensEmConflito($itens);
            if ($conflitos !== []) {
                $alertas[] = [
                    'aluno_id' => $r['aluno_id'],
                    'aluno' => $nomes[$r['aluno_id']] ?? ('Aluno #'.$r['aluno_id']),
                    'restricao' => $r['restricao'],
                    'itens' => array_values(array_unique(array_column($conflitos, 'item'))),
                ];
            }
        }

        $refeicoesArr = $cardapio->toArray()['refeicoes'] ?? [];
        $pnae = PnaeRegras::avaliar($refeicoesArr);
        $horarios = HorarioCardapio::avaliar($turmaId > 0 ? $turmaId : null, $refeicoesArr, $escola);

        return view('alimentacao::cardapio', [
            'cardapio' => $cardapio->toArray(),
            'restricoes' => $restricoes,
            'alertas' => $alertas,
            'pnae' => $pnae,
            'horarios' => $horarios,
            'fonte' => $fonte,
            'data' => $data,
            'escola' => $escola,
            'turma' => $turma,
            'turma_id' => $turmaId,
            'turmasSelecionadas' => array_column($turmasSel, 'nome'),
            'escopo' => $escopo,
            'segmento' => $segmento,
            'segmentosSelecionados' => $segmentosSel,
            'escopos' => EscopoCardapio::opcoes(),
            'segmentos' => EscopoCardapio::segmentos(),
            'confirmado_em' => $confirmadoEm,
            'cardapio_id' => $cardapioId,
            'escolas' => $this->escolasDaRede(),
            'turmas' => $this->turmasDaEscola($escola),
            'catalogo' => $catalogo,
            'sugestaoVals' => $sugestaoVals,
            'refeicoesUi' => ConfigAlimentacao::slotsUi(),
            'podeMontar' => (bool) config('alimentacao.montar_cardapio', true)
                && (bool) ConfigAlimentacao::get('montar_cardapio', true),
            'rt' => ConfigAlimentacao::responsavelTecnico(),
        ]);
    }

    public function salvarCardapio(Request $request): RedirectResponse
    {
        if (! (bool) config('alimentacao.montar_cardapio', true)) {
            return back()->with('erro', 'Montagem de cardápio desabilitada por config (ALIMENTACAO_MONTAR_CARDAPIO).');
        }
        if (! Schema::hasTable('alimentacao.cardapio_dia')) {
            return back()->with('erro', 'Schema alimentacao.cardapio_dia ausente — rode migrate com PackageTenant liberado.');
        }

        $data = (string) $request->input('data', date('Y-m-d'));
        $escopo = EscopoCardapio::normalizar((string) $request->input('escopo', EscopoCardapio::ESCOLA));
        $escola = trim((string) $request->input('escola', 'Rede')) ?: 'Rede';
        $turmas = EscopoCardapio::normalizarTurmas(
            $request->input('turmas', []),
            $request->input('turma', ''),
            $request->input('turma_id', 0)
        );
        $segmentos = EscopoCardapio::normalizarSegmentos(
            $request->input('segmentos', []),
            $request->input('segmento')
        );
        $expandido = EscopoCardapio::expandirDestinos($escopo, $escola, $turmas, $segmentos);
        if (! $expandido['ok']) {
            return back()->withInput()->with('erro', $expandido['erro']);
        }
        $escola = $expandido['escola'];
        $escopo = $expandido['escopo'];
        $destinos = $expandido['destinos'];

        $montagem = BandejasAutomaticas::completar($this->refeicoesDoRequest($request));
        $refeicoes = $montagem['refeicoes'];
        $bandejasAuto = (bool) $montagem['automatico'];
        if ($refeicoes === []) {
            return back()->with('erro', 'Catálogo vazio — cadastre itens antes de lançar o cardápio.');
        }

        $pnae = PnaeRegras::avaliar($refeicoes);
        if ((bool) config('alimentacao.pnae.bloquear_erro', false)
            && collect($pnae)->contains(static fn (array $a): bool => $a['nivel'] === 'erro')) {
            return back()->withInput()->with('erro', $pnae[0]['mensagem'] ?? 'Cardápio bloqueado por regra PNAE.');
        }

        $confirmar = $request->input('acao') === 'confirmar' || $request->boolean('confirmar');
        $json = json_encode($refeicoes, JSON_UNESCAPED_UNICODE);
        $usuarioId = Auth::id() ? (int) Auth::id() : null;

        foreach ($destinos as $destino) {
            CardapioLote::upsertDiaPublico(
                $data,
                $destino['escola'],
                $destino['turma'],
                $destino['turma_id'] > 0 ? $destino['turma_id'] : null,
                $json,
                $escopo,
                $destino['segmento'],
                'diario',
                $confirmar,
                $usuarioId,
            );

            $segsEst = $destino['segmento'] !== null ? [$destino['segmento']] : [];
            PerfilEscolar::garantirEstimativas(
                $data,
                $destino['escola'],
                $refeicoes,
                $segsEst,
                $destino['turma'] !== '' ? $destino['turma'] : null,
                $usuarioId
            );
        }

        $primeiro = $destinos[0];
        $turma = $primeiro['turma'];
        $turmaId = $primeiro['turma_id'];
        $segmento = (string) ($primeiro['segmento'] ?? '');
        $nDest = count($destinos);
        $rotuloDest = $nDest === 1
            ? ($turma !== '' ? ' / '.$turma : ($segmento !== '' ? ' / '.$segmento : ''))
            : ' · '.$nDest.' destinos';

        $qs = http_build_query(array_filter([
            'data' => $data,
            'escola' => $escola,
            'turma' => $turma !== '' ? $turma : null,
            'turma_id' => $turmaId > 0 ? $turmaId : null,
            'escopo' => $escopo,
            'segmento' => $segmento !== '' ? $segmento : null,
        ]));

        AuditoriaFluxo::registrar(
            null,
            'cardapio',
            "Cardápio {$escopo} · {$escola}{$rotuloDest} em {$data}"
                .($confirmar ? ' (confirmado)' : '')
                .($bandejasAuto ? ' (bandejas automáticas)' : ''),
            null,
            [
                'escola' => $escola,
                'data' => $data,
                'escopo' => $escopo,
                'destinos' => $destinos,
                'refeicoes' => $refeicoes,
                'bandejas_auto' => $bandejasAuto,
            ]
        );

        $avisoHorario = HorarioCardapio::avaliar($turmaId > 0 ? $turmaId : null, $refeicoes, $escola);
        $avisoPnae = collect($pnae)->first(static fn (array $a): bool => in_array($a['nivel'], ['erro', 'alerta'], true));
        $avisoHora = collect($avisoHorario)->first(static fn (array $a): bool => $a['nivel'] === 'erro');
        $msg = 'Cardápio '.$escopo.' · '.$escola.$rotuloDest." salvo para {$data}."
            .($confirmar ? ' Confirmado para execução.' : '')
            .($bandejasAuto ? ' Bandejas montadas automaticamente pelo catálogo.' : '');
        $redirect = redirect('/alimentacao-escolar/cardapio?'.$qs)->with('ok', $msg);
        if ($avisoHora) {
            $redirect = $redirect->with('alerta', $avisoHora['mensagem']);
        } elseif ($avisoPnae) {
            $redirect = $redirect->with('alerta', $avisoPnae['mensagem']);
        }

        return $redirect;
    }

    public function cardapioLote(Request $request): View
    {
        return view('alimentacao::cardapio-lote', [
            'escolas' => $this->escolasDaRede(),
            'catalogo' => CatalogoItens::listar(),
            'data_inicio' => (string) $request->query('data_inicio', date('Y-m-d')),
            'data_fim' => (string) $request->query('data_fim', date('Y-m-d', strtotime('+4 weekdays'))),
            'escopo' => EscopoCardapio::normalizar((string) $request->query('escopo', EscopoCardapio::ESCOLA)),
            'segmento' => (string) $request->query('segmento', ''),
            'escopos' => EscopoCardapio::opcoes(),
            'segmentos' => EscopoCardapio::segmentos(),
            'refeicoesUi' => ConfigAlimentacao::slotsUi(),
            'podeMontar' => (bool) config('alimentacao.montar_cardapio', true)
                && (bool) ConfigAlimentacao::get('montar_cardapio', true),
        ]);
    }

    public function salvarCardapioLote(Request $request): RedirectResponse
    {
        if (! (bool) config('alimentacao.montar_cardapio', true)) {
            return back()->with('erro', 'Montagem de cardápio desabilitada por config.');
        }

        $montagem = BandejasAutomaticas::completar($this->refeicoesDoRequest($request));
        $refeicoes = $montagem['refeicoes'];
        $bandejasAuto = (bool) $montagem['automatico'];
        if ($refeicoes === []) {
            return back()->withInput()->with('erro', 'Catálogo vazio — cadastre itens antes de lançar o cardápio.');
        }
        $escopo = EscopoCardapio::normalizar((string) $request->input('escopo', EscopoCardapio::ESCOLA));
        $turmas = EscopoCardapio::normalizarTurmas(
            $request->input('turmas', []),
            $request->input('turma', ''),
            $request->input('turma_id', 0)
        );
        $turmasTexto = trim((string) $request->input('turmas_texto', ''));
        if ($turmasTexto !== '') {
            foreach (preg_split('/[,;\n]+/', $turmasTexto) ?: [] as $nomeTurma) {
                $extra = EscopoCardapio::normalizarTurmas([], trim((string) $nomeTurma), 0);
                foreach ($extra as $t) {
                    $turmas[] = $t;
                }
            }
            $turmas = EscopoCardapio::normalizarTurmas(
                array_map(static fn (array $t): string => (($t['id'] ?? 0) > 0 ? $t['id'].'::' : '').$t['nome'], $turmas)
            );
        }
        $segmentos = EscopoCardapio::normalizarSegmentos(
            $request->input('segmentos', []),
            $request->input('segmento')
        );
        $escolas = array_values(array_filter((array) $request->input('escolas', [])));
        if ($escolas === [] && $request->filled('escola')) {
            $escolas = [trim((string) $request->input('escola'))];
        }
        if ($escopo === EscopoCardapio::REDE) {
            $escolas = ['Rede'];
        }
        $probe = EscopoCardapio::expandirDestinos($escopo, $escolas[0] ?? 'Rede', $turmas, $segmentos);
        if (! $probe['ok'] && $escopo !== EscopoCardapio::ESCOLA) {
            return back()->withInput()->with('erro', $probe['erro']);
        }
        if ($escopo === EscopoCardapio::ESCOLA && $escolas === []) {
            return back()->withInput()->with('erro', 'Selecione ao menos uma escola ou o escopo Rede.');
        }

        try {
            $resultado = CardapioLote::gravar(
                (string) $request->input('data_inicio', date('Y-m-d')),
                (string) $request->input('data_fim', date('Y-m-d')),
                $escolas,
                $refeicoes,
                $turmas,
                null,
                $escopo,
                $segmentos,
                'semanal',
                $request->input('acao') === 'confirmar' || $request->boolean('confirmar'),
                Auth::id() ? (int) Auth::id() : null,
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }

        $confirmado = $request->input('acao') === 'confirmar' || $request->boolean('confirmar');
        $aviso = collect($resultado['pnae'])->first(static fn (array $a): bool => in_array($a['nivel'], ['erro', 'alerta'], true));
        $redirect = redirect('/alimentacao-escolar/cardapio-lote')
            ->with('ok', $resultado['gravados'].' cardápio(s) gravado(s) em lote'
                .($confirmado ? ' e confirmados.' : '.')
                .($bandejasAuto ? ' Bandejas montadas automaticamente pelo catálogo.' : ''));

        return $aviso ? $redirect->with('alerta', $aviso['mensagem']) : $redirect;
    }

    public function itens(): View
    {
        $itens = collect(CatalogoItens::listar(false))
            ->sortBy([
                static fn (array $i): int => ! empty($i['ativo']) ? 0 : 1,
                static fn (array $i): string => mb_strtolower($i['nome']),
            ])
            ->values()
            ->all();
        $semNutricao = collect($itens)->filter(static fn (array $i): bool => empty($i['completo']) && ! empty($i['ativo']))->count();
        $podeEditar = PermissoesAlimentacao::podeEditarItens();

        return view('alimentacao::itens', [
            'itens' => $itens,
            'podeMontar' => $podeEditar,
            'podeEditar' => $podeEditar,
            'semNutricao' => $semNutricao,
            'editarId' => (int) request()->query('editar', 0),
        ]);
    }

    public function salvarItem(Request $request): RedirectResponse
    {
        if (! PermissoesAlimentacao::podeEditarItens()) {
            return back()->with('erro', 'Sem permissão para cadastrar ou editar itens do catálogo.');
        }

        $id = (int) $request->input('id', 0) ?: null;

        try {
            CatalogoItens::salvar(
                $id,
                [
                    'nome' => (string) $request->input('nome', ''),
                    'categoria' => (string) $request->input('categoria', 'in_natura'),
                    'grupo_pnae' => (string) $request->input('grupo_pnae', 'outro'),
                    'origem_af' => $request->boolean('origem_af'),
                    'ativo' => $request->boolean('ativo', true),
                    'unidade' => (string) $request->input('unidade', 'porção'),
                    'peso_porcao_g' => $request->input('peso_porcao_g'),
                    'kcal_porcao' => $request->input('kcal_porcao'),
                    'proteina_g' => $request->input('proteina_g'),
                    'carboidrato_g' => $request->input('carboidrato_g'),
                    'lipidio_g' => $request->input('lipidio_g'),
                    'fibra_g' => $request->input('fibra_g'),
                    'sodio_mg' => $request->input('sodio_mg'),
                    'custo_unitario' => $request->input('custo_unitario'),
                    'padrao_pnae' => $request->boolean('padrao_pnae', true),
                    'refeicoes' => (array) $request->input('refeicoes', ['cafe', 'almoco', 'lanche']),
                ]
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }

        $msg = ($id !== null && $id > 0)
            ? 'Item atualizado — peso, kcal e custo alimentam o balanço e as compras.'
            : 'Item cadastrado no catálogo PNAE.';

        return redirect('/alimentacao-escolar/itens')->with('ok', $msg);
    }

    /**
     * @return list<array{refeicao:string,itens:list<string>,porcoes:list<array{item:string,peso_g:?float,kcal:?float,previsto:bool}>}>
     */
    private function refeicoesDoRequest(Request $request): array
    {
        $refeicoes = [];
        $pesos = (array) $request->input('peso_g', []);
        $kcals = (array) $request->input('kcal', []);
        $slots = array_keys(ConfigAlimentacao::slotsUi());
        foreach ($slots as $campo) {
            $rawLista = $request->input($campo.'_itens');
            if (is_array($rawLista)) {
                $itens = array_values(array_filter(array_map(
                    static fn ($s): string => trim((string) $s),
                    $rawLista
                ), static fn (string $s): bool => $s !== ''));
            } else {
                $raw = (string) $request->input($campo, '');
                $itens = array_values(array_filter(array_map(
                    static fn (string $s): string => trim($s),
                    explode(',', $raw)
                ), static fn (string $s): bool => $s !== ''));
            }
            if ($itens === []) {
                continue;
            }
            $overrides = [];
            foreach ($itens as $nome) {
                $chave = mb_strtolower($nome);
                $overrides[$chave] = [
                    'peso_g' => $pesos[$campo][$chave] ?? $pesos[$chave] ?? null,
                    'kcal' => $kcals[$campo][$chave] ?? $kcals[$chave] ?? null,
                ];
            }
            $refeicoes[] = [
                'refeicao' => $campo,
                'itens' => $itens,
                'porcoes' => CatalogoItens::porcoesPrevistas($itens, $overrides),
            ];
        }

        return $refeicoes;
    }

    public function perfil(Request $request): View
    {
        $data = (string) $request->query('data', date('Y-m-d'));
        $escola = (string) $request->query('escola', '');
        $soFatos = PermissoesAlimentacao::soRegistraFatos();

        return view('alimentacao::perfil', [
            'data' => $data,
            'escola' => $escola,
            'escolas' => $this->escolasDaRede(),
            'segmentos' => EscopoCardapio::segmentos(),
            'slots' => ConfigAlimentacao::slotsUi(),
            'servicos' => PerfilEscolar::listar($data, $escola !== '' ? $escola : null),
            'soFatos' => $soFatos,
            'podeMontar' => (bool) config('alimentacao.montar_cardapio', true),
        ]);
    }

    public function salvarPerfil(Request $request): RedirectResponse
    {
        $soFatos = PermissoesAlimentacao::soRegistraFatos();
        $segmentos = array_values(array_filter(array_map(
            static fn ($s): string => trim((string) $s),
            (array) $request->input('segmentos', [])
        )));
        if ($segmentos === [] && $request->filled('segmento')) {
            $segmentos = [trim((string) $request->input('segmento'))];
        }

        try {
            PerfilEscolar::registrar([
                'data' => (string) $request->input('data', date('Y-m-d')),
                'escola' => (string) $request->input('escola', ''),
                'refeicao' => (string) $request->input('refeicao', 'almoco'),
                'porcoes_previstas' => $soFatos ? 0 : (int) $request->input('porcoes_previstas', 0),
                'porcoes_servidas' => (int) $request->input('porcoes_servidas', 0),
                'segmentos' => $segmentos,
                'turma' => $request->input('turma'),
                'observacao' => $request->input('observacao'),
                'confirmar' => $request->boolean('confirmar'),
                'usuario_id' => Auth::id() ? (int) Auth::id() : null,
                'somente_fatos' => $soFatos,
                'origem' => 'manual',
            ]);
        } catch (\Throwable $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }

        $qs = http_build_query(array_filter([
            'data' => (string) $request->input('data', date('Y-m-d')),
            'escola' => (string) $request->input('escola', ''),
        ]));

        return redirect('/alimentacao-escolar/perfil?'.$qs)
            ->with('ok', $soFatos
                ? 'Fato registrado (porções servidas).'
                : 'Porções registradas'.($request->boolean('confirmar') ? ' e confirmadas.' : '.'));
    }

    public function cardapioImpressao(Request $request): View|\Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        $data = (string) $request->input('data', $request->query('data', date('Y-m-d')));
        $escola = (string) $request->input('escola', $request->query('escola', 'Rede'));
        $turma = trim((string) $request->input('turma', $request->query('turma', '')));
        $segmento = trim((string) $request->input('segmento', $request->query('segmento', '')));
        $escopo = EscopoCardapio::normalizar((string) $request->input('escopo', $request->query('escopo', EscopoCardapio::ESCOLA)));
        $formato = strtolower(trim((string) $request->input('formato', $request->query('formato', 'pdf'))));
        $fallbackHtml = $request->boolean('html') || $formato === 'legado';

        $cardapio = $this->carregarCardapioImpressao($data, $escola, $turma, $segmento, $escopo);
        $rt = ConfigAlimentacao::responsavelTecnico();
        $slots = ConfigAlimentacao::slotsUi();
        $segmentos = EscopoCardapio::segmentos();

        if (! $fallbackHtml && class_exists(\iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::class)) {
            $porSlot = [];
            foreach (($cardapio['refeicoes'] ?? []) as $ref) {
                $k = mb_strtolower((string) ($ref['refeicao'] ?? ''));
                $porSlot[$k] = array_values(array_filter(array_map('strval', $ref['itens'] ?? [])));
            }

            $linhas = [];
            foreach ($slots as $cod => $meta) {
                $itens = $porSlot[$cod] ?? [];
                $linhas[] = [
                    (string) ($meta['rotulo'] ?? $cod),
                    $itens !== [] ? implode(', ', $itens) : '— sem itens —',
                ];
            }
            if ($linhas === []) {
                $linhas[] = ['—', 'Nenhum cardápio encontrado para os filtros.'];
            }

            $dataFmt = $data;
            try {
                $dataFmt = \Illuminate\Support\Carbon::parse($data)->format('d/m/Y');
            } catch (\Throwable) {
            }

            $subtitulo = ($cardapio['escola'] ?? $escola)
                .' · '.$dataFmt
                .(! empty($cardapio['turma']) ? ' · Turma '.$cardapio['turma'] : '')
                .(! empty($cardapio['segmento'])
                    ? ' · '.($segmentos[$cardapio['segmento']] ?? $cardapio['segmento'])
                    : '')
                .' · Escopo '.(EscopoCardapio::opcoes()[$escopo] ?? $escopo);

            $assinaturas = [
                [
                    'nome' => (string) ($rt['nome'] ?? ''),
                    'cargo' => trim(((string) ($rt['cargo'] ?? 'Responsável técnico'))
                        .(! empty($rt['registro']) ? ' · '.$rt['registro'] : '')),
                    'quando' => null,
                ],
                [
                    'nome' => '',
                    'cargo' => 'Direção / Unidade — ciência e divulgação',
                    'quando' => null,
                ],
            ];

            return \iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::download(
                'Cardápio escolar',
                ['Refeição', 'Itens'],
                $linhas,
                'Alimentação escolar',
                $formato !== '' ? $formato : 'pdf',
                $subtitulo,
                Auth::user()?->name ?? Auth::user()?->email,
                'alimentacao-cardapio-'.$data,
                true,
                $assinaturas,
            );
        }

        return view('alimentacao::cardapio-pdf', [
            'cardapio' => $cardapio,
            'slots' => $slots,
            'segmentos' => $segmentos,
            'rt' => $rt,
            'identidade' => \iEducar\Packages\BuritiAlimentacaoEscolar\Support\Identidade::tokens(),
        ]);
    }

    /**
     * @return array{data:string,escola:string,turma?:string,segmento?:string,escopo?:string,refeicoes:list<array{refeicao?:string,itens?:list<string>}>}
     */
    private function carregarCardapioImpressao(
        string $data,
        string $escola,
        string $turma,
        string $segmento,
        string $escopo,
    ): array {
        $cardapio = ['data' => $data, 'escola' => $escola, 'turma' => $turma, 'segmento' => $segmento, 'escopo' => $escopo, 'refeicoes' => []];
        if (! Schema::hasTable('alimentacao.cardapio_dia')) {
            return $cardapio;
        }

        $q = DB::table('alimentacao.cardapio_dia')->where('data', $data)->where('escola', $escola);
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'escopo')) {
            $q->where('escopo', $escopo);
        }
        if ($turma !== '' && Schema::hasColumn('alimentacao.cardapio_dia', 'turma')) {
            $q->where('turma', $turma);
        }
        if ($segmento !== '' && Schema::hasColumn('alimentacao.cardapio_dia', 'segmento')) {
            $q->where('segmento', $segmento);
        }
        $row = $q->first() ?? DB::table('alimentacao.cardapio_dia')->where('data', $data)->where('escola', $escola)->first();
        if ($row === null) {
            return $cardapio;
        }

        $refs = is_string($row->refeicoes) ? (json_decode($row->refeicoes, true) ?: []) : (array) $row->refeicoes;

        return [
            'data' => (string) $row->data,
            'escola' => (string) $row->escola,
            'turma' => (string) ($row->turma ?? $turma),
            'segmento' => (string) ($row->segmento ?? $segmento),
            'escopo' => (string) ($row->escopo ?? $escopo),
            'refeicoes' => $refs,
        ];
    }

    public function cronogramaPdf(Request $request): View
    {
        $inicio = (string) $request->query('inicio', date('Y-m-d', strtotime('monday this week')));
        $fim = (string) $request->query('fim', date('Y-m-d', strtotime('friday this week')));
        $escola = (string) $request->query('escola', 'Rede');
        $turma = trim((string) $request->query('turma', ''));
        $segmento = trim((string) $request->query('segmento', ''));
        $escopo = EscopoCardapio::normalizar((string) $request->query('escopo', EscopoCardapio::ESCOLA));

        $cronograma = [];
        $restricoes = [];
        $totaisNutricao = ['kcal' => 0, 'proteina' => 0, 'carboidrato' => 0, 'lipidio' => 0, 'fibra' => 0, 'dias' => 0];

        if (Schema::hasTable('alimentacao.cardapio_dia')) {
            $q = DB::table('alimentacao.cardapio_dia')
                ->whereBetween('data', [$inicio, $fim])
                ->where('escola', $escola)
                ->orderBy('data');

            if (Schema::hasColumn('alimentacao.cardapio_dia', 'escopo')) {
                $q->where('escopo', $escopo);
            }
            if ($turma !== '' && Schema::hasColumn('alimentacao.cardapio_dia', 'turma')) {
                $q->where('turma', $turma);
            }
            if ($segmento !== '' && Schema::hasColumn('alimentacao.cardapio_dia', 'segmento')) {
                $q->where('segmento', $segmento);
            }

            $rows = $q->get();
            foreach ($rows as $row) {
                $refs = is_string($row->refeicoes) ? (json_decode($row->refeicoes, true) ?: []) : (array) $row->refeicoes;
                
                $kcalDia = 0;
                $proteinaDia = 0;
                $carboidratoDia = 0;
                $lipidioDia = 0;
                $fibraDia = 0;

                foreach ($refs as $ref) {
                    foreach (($ref['porcoes'] ?? []) as $p) {
                        $kcalDia += (float) ($p['kcal'] ?? 0);
                        $proteinaDia += (float) ($p['proteina_g'] ?? 0);
                        $carboidratoDia += (float) ($p['carboidrato_g'] ?? 0);
                        $lipidioDia += (float) ($p['lipidio_g'] ?? 0);
                        $fibraDia += (float) ($p['fibra_g'] ?? 0);
                    }
                }

                $cronograma[] = [
                    'data' => (string) $row->data,
                    'escola' => (string) $row->escola,
                    'turma' => (string) ($row->turma ?? ''),
                    'segmento' => (string) ($row->segmento ?? ''),
                    'escopo' => (string) ($row->escopo ?? $escopo),
                    'refeicoes' => $refs,
                    'nutricao' => [
                        'kcal' => $kcalDia,
                        'proteina' => $proteinaDia,
                        'carboidrato' => $carboidratoDia,
                        'lipidio' => $lipidioDia,
                        'fibra' => $fibraDia,
                    ],
                ];

                $totaisNutricao['kcal'] += $kcalDia;
                $totaisNutricao['proteina'] += $proteinaDia;
                $totaisNutricao['carboidrato'] += $carboidratoDia;
                $totaisNutricao['lipidio'] += $lipidioDia;
                $totaisNutricao['fibra'] += $fibraDia;
                $totaisNutricao['dias']++;
            }
        }

        // Buscar restrições relevantes
        if (Schema::hasTable('alimentacao.restricao_aluno')) {
            $rq = DB::table('alimentacao.restricao_aluno')->orderBy('restricao')->limit(50);
            $restricoes = $rq->get()->map(static fn ($r): array => [
                'aluno_id' => (int) ($r->ref_cod_aluno ?? 0),
                'restricao' => (string) $r->restricao,
                'observacao' => (string) ($r->observacao ?? ''),
            ])->all();
        }

        $mediasNutricao = [];
        if ($totaisNutricao['dias'] > 0) {
            $mediasNutricao = [
                'kcal' => round($totaisNutricao['kcal'] / $totaisNutricao['dias'], 1),
                'proteina' => round($totaisNutricao['proteina'] / $totaisNutricao['dias'], 1),
                'carboidrato' => round($totaisNutricao['carboidrato'] / $totaisNutricao['dias'], 1),
                'lipidio' => round($totaisNutricao['lipidio'] / $totaisNutricao['dias'], 1),
                'fibra' => round($totaisNutricao['fibra'] / $totaisNutricao['dias'], 1),
            ];
        }

        return view('alimentacao::cronograma-pdf', [
            'cronograma' => $cronograma,
            'inicio' => $inicio,
            'fim' => $fim,
            'escola' => $escola,
            'turma' => $turma,
            'segmento' => $segmento,
            'escopo' => $escopo,
            'slots' => ConfigAlimentacao::slotsUi(),
            'segmentos' => EscopoCardapio::segmentos(),
            'rt' => ConfigAlimentacao::responsavelTecnico(),
            'restricoes' => $restricoes,
            'totaisNutricao' => $totaisNutricao,
            'mediasNutricao' => $mediasNutricao,
            'identidade' => \iEducar\Packages\BuritiAlimentacaoEscolar\Support\Identidade::tokens(),
        ]);
    }

    public function turmasJson(Request $request): JsonResponse
    {
        $escola = (string) $request->query('escola', '');

        return response()->json([
            'turmas' => $this->turmasDaEscola($escola),
        ]);
    }

    public function configuracoes(): RedirectResponse
    {
        return redirect('/configuracoes/modulos?modulo=alimentacao')
            ->with('ok', 'Configurações da alimentação ficam em Configurações → Módulos.');
    }

    public function salvarConfiguracoes(Request $request): RedirectResponse
    {
        return redirect('/configuracoes/modulos?modulo=alimentacao')
            ->with('ok', 'Use Configurações → Módulos para salvar as opções da alimentação.');
    }

    public function balanco(Request $request): View
    {
        $inicio = (string) $request->query('inicio', date('Y-m-d', strtotime('monday this week')));
        $fim = (string) $request->query('fim', date('Y-m-d'));
        $escola = (string) $request->query('escola', '');

        return view('alimentacao::balanco', [
            'inicio' => $inicio,
            'fim' => $fim,
            'escola' => $escola,
            'escolas' => $this->escolasDaRede(),
            'balanco' => BalancoAlimentacao::gerar($inicio, $fim, $escola !== '' ? $escola : null),
        ]);
    }

    public function restricoes(): View
    {
        $linhas = [];
        if (Schema::hasTable('alimentacao.restricao_aluno')) {
            $linhas = DB::table('alimentacao.restricao_aluno')
                ->orderBy('ref_cod_aluno')
                ->limit(200)
                ->get()
                ->all();
        }

        $ids = array_map(static fn ($r): int => (int) ($r->ref_cod_aluno ?? 0), $linhas);
        $nomes = $this->nomesAlunos($ids);

        return view('alimentacao::restricoes', [
            'linhas' => $linhas,
            'nomes' => $nomes,
            'alunos' => $this->alunosDaRede(),
            'podeMontar' => (bool) config('alimentacao.montar_cardapio', true),
        ]);
    }

    public function salvarRestricao(Request $request): RedirectResponse
    {
        if (! (bool) config('alimentacao.montar_cardapio', true)) {
            return back()->with('erro', 'Cadastro de restrição desabilitado por config.');
        }
        if (! Schema::hasTable('alimentacao.restricao_aluno')) {
            return back()->with('erro', 'Schema alimentacao.restricao_aluno ausente.');
        }

        $aluno = (int) $request->input('aluno_id', $request->input('aluno_id_manual', 0));
        $restricao = trim((string) $request->input('restricao', ''));
        $obs = trim((string) $request->input('observacao', ''));

        if ($aluno <= 0 || $restricao === '') {
            return back()->with('erro', 'Selecione um aluno (ou informe o código) e a restrição.');
        }

        $dados = [
            'updated_at' => now(),
            'created_at' => now(),
        ];
        if (Schema::hasColumn('alimentacao.restricao_aluno', 'observacao')) {
            $dados['observacao'] = $obs !== '' ? $obs : null;
        }

        if ($request->hasFile('laudo')) {
            try {
                $anexo = AnexoFluxo::salvar(
                    $request->file('laudo'),
                    'alimentacao/laudos/'.$aluno,
                    ['pdf', 'jpg', 'jpeg', 'png'],
                    (int) config('alimentacao.laudo_max_kb', 5120)
                );
                if (Schema::hasColumn('alimentacao.restricao_aluno', 'laudo_path')) {
                    $dados['laudo_path'] = $anexo['path'];
                    $dados['laudo_nome'] = $anexo['nome'];
                }
            } catch (Throwable $e) {
                return back()->with('erro', 'Laudo: '.$e->getMessage());
            }
        }

        DB::table('alimentacao.restricao_aluno')->updateOrInsert(
            ['ref_cod_aluno' => $aluno, 'restricao' => $restricao],
            $dados
        );

        AuditoriaFluxo::registrar(
            null,
            'restricao',
            "Restrição '{$restricao}' aluno #{$aluno}"
                .(isset($dados['laudo_path']) ? ' + laudo' : ''),
            $aluno
        );

        return redirect('/alimentacao-escolar/restricoes')
            ->with('ok', "Restrição '{$restricao}' registrada para aluno #{$aluno}"
                .(isset($dados['laudo_path']) ? ' com laudo anexado.' : '.'));
    }

    public function baixarLaudo(int $id): BinaryFileResponse|RedirectResponse
    {
        if (! Schema::hasTable('alimentacao.restricao_aluno')) {
            return redirect('/alimentacao-escolar/restricoes')->with('erro', 'Schema ausente.');
        }
        $row = DB::table('alimentacao.restricao_aluno')->where('id', $id)->first();
        if ($row === null || empty($row->laudo_path ?? null)) {
            return redirect('/alimentacao-escolar/restricoes')->with('erro', 'Laudo não encontrado.');
        }
        $abs = AnexoFluxo::caminhoAbsoluto((string) $row->laudo_path);
        if ($abs === null) {
            return redirect('/alimentacao-escolar/restricoes')->with('erro', 'Arquivo do laudo ausente no disco.');
        }

        return response()->download($abs, (string) ($row->laudo_nome ?? 'laudo.pdf'));
    }

    public function historico(): View
    {
        $linhas = [];
        if (Schema::hasTable('laguna.auditoria')) {
            $linhas = DB::table('laguna.auditoria')
                ->whereIn('acao', ['cardapio', 'restricao', 'item', 'config', 'perfil'])
                ->orderByDesc('id')
                ->limit(100)
                ->get()
                ->map(static fn ($r): array => [
                    'quando' => (string) ($r->created_at ?? ''),
                    'acao' => (string) $r->acao,
                    'detalhe' => (string) ($r->detalhe ?? ''),
                    'usuario' => (string) ($r->usuario ?? '—'),
                ])
                ->all();
        }

        return view('alimentacao::historico', ['linhas' => $linhas]);
    }

    public function mapa(): View
    {
        $linhas = $this->consolidarMapaDoBanco();
        $fonte = $linhas === [] ? 'demo' : 'alimentacao.cardapio_dia';

        if ($linhas === []) {
            $linhas = [
                ['escola' => 'EMEF Norte', 'refeicao' => 'almoco', 'quantidade' => 180, 'data' => date('Y-m-d')],
                ['escola' => 'EMEF Norte', 'refeicao' => 'cafe', 'quantidade' => 160, 'data' => date('Y-m-d')],
                ['escola' => 'EMEF Sul', 'refeicao' => 'almoco', 'quantidade' => 140, 'data' => date('Y-m-d')],
                ['escola' => 'EMEF Sul', 'refeicao' => 'lanche', 'quantidade' => 95, 'data' => date('Y-m-d')],
                ['escola' => 'EMEF Centro', 'refeicao' => 'almoco', 'quantidade' => 210, 'data' => date('Y-m-d')],
                ['escola' => 'EMEF Centro', 'refeicao' => 'cafe', 'quantidade' => 190, 'data' => date('Y-m-d')],
            ];
        }

        $mapa = MapaDistribuicao::consolidar($linhas);

        return view('alimentacao::mapa', [
            'mapa' => $mapa,
            'fonte' => $fonte,
            'porMatriculas' => (bool) config('alimentacao.mapa_por_matriculas', false),
            'escolaFoco' => trim((string) request()->query('escola', '')),
        ]);
    }

    public function demo(): View
    {
        return view('alimentacao::demo');
    }

    public function rodarDemo(): RedirectResponse
    {
        try {
            Artisan::call('alimentacao:demo');
            $out = Artisan::output();

            return redirect('/alimentacao-escolar/cardapio')
                ->with('ok', 'Demo executada. '.$out);
        } catch (\Throwable $e) {
            return redirect('/alimentacao-escolar/demo')
                ->with('erro', 'Falha na demo: '.$e->getMessage());
        }
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<int,string>
     */
    private function nomesAlunos(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ($ids === [] || ! Schema::hasTable('pmieducar.aluno') || ! Schema::hasTable('cadastro.pessoa')) {
            return [];
        }
        try {
            return DB::table('pmieducar.aluno')
                ->join('cadastro.pessoa', 'cadastro.pessoa.idpes', '=', 'pmieducar.aluno.ref_idpes')
                ->whereIn('pmieducar.aluno.cod_aluno', $ids)
                ->pluck('cadastro.pessoa.nome', 'pmieducar.aluno.cod_aluno')
                ->mapWithKeys(static fn ($nome, $id): array => [(int) $id => (string) $nome])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array{id:int,nome:string}>
     */
    private function alunosDaRede(int $limite = 400): array
    {
        if (! Schema::hasTable('pmieducar.aluno') || ! Schema::hasTable('cadastro.pessoa')) {
            return [];
        }
        try {
            return DB::table('pmieducar.aluno')
                ->join('cadastro.pessoa', 'cadastro.pessoa.idpes', '=', 'pmieducar.aluno.ref_idpes')
                ->where('pmieducar.aluno.ativo', 1)
                ->orderBy('cadastro.pessoa.nome')
                ->limit($limite)
                ->get(['pmieducar.aluno.cod_aluno as id', 'cadastro.pessoa.nome'])
                ->map(static fn ($r): array => [
                    'id' => (int) $r->id,
                    'nome' => (string) $r->nome,
                ])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return list<array{id:int,nome:string}>
     */
    private function turmasDaEscola(string $escolaNome): array
    {
        if (! Schema::hasTable('pmieducar.turma') || ! Schema::hasTable('pmieducar.escola') || ! Schema::hasTable('cadastro.juridica')) {
            return [];
        }
        try {
            $cod = DB::table('pmieducar.escola')
                ->join('cadastro.juridica', 'cadastro.juridica.idpes', '=', 'pmieducar.escola.ref_idpes')
                ->where('pmieducar.escola.ativo', 1)
                ->where('cadastro.juridica.fantasia', $escolaNome)
                ->value('pmieducar.escola.cod_escola');
            if ($cod === null) {
                return [];
            }

            return DB::table('pmieducar.turma')
                ->where('ativo', 1)
                ->where('ano', (int) date('Y'))
                ->where('ref_ref_cod_escola', (int) $cod)
                ->orderBy('nm_turma')
                ->limit(80)
                ->get(['cod_turma as id', 'nm_turma as nome'])
                ->map(static fn ($r): array => ['id' => (int) $r->id, 'nome' => (string) $r->nome])
                ->all();
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Lista de escolas (nome fantasia + cod_escola) para o select.
     * Respeita config alimentacao.escolas_do_nucleo — quando desligado,
     * devolve só a opção "Rede" para evitar JOIN no núcleo legado.
     *
     * @return list<array{cod:int|null,nome:string}>
     */
    private function escolasDaRede(): array
    {
        $out = [['cod' => null, 'nome' => 'Rede']];

        if (! (bool) config('alimentacao.escolas_do_nucleo', true)) {
            return $out;
        }
        if (! Schema::hasTable('pmieducar.escola') || ! Schema::hasTable('cadastro.juridica')) {
            return $out;
        }

        try {
            $rows = DB::table('pmieducar.escola')
                ->join('cadastro.juridica', 'cadastro.juridica.idpes', '=', 'pmieducar.escola.ref_idpes')
                ->where('pmieducar.escola.ativo', 1)
                ->orderBy('cadastro.juridica.fantasia')
                ->limit(200)
                ->get(['pmieducar.escola.cod_escola', 'cadastro.juridica.fantasia']);
        } catch (\Throwable) {
            return $out;
        }

        foreach ($rows as $r) {
            $out[] = ['cod' => (int) $r->cod_escola, 'nome' => (string) ($r->fantasia ?? ('Escola '.$r->cod_escola))];
        }

        return $out;
    }

    /**
     * Agrega as refeições persistidas por escola/refeição em "quantidade"
     * (número de itens * fator de matrículas, quando habilitado).
     *
     * @return list<array{escola:string,refeicao:string,quantidade:int}>
     */
    private function consolidarMapaDoBanco(): array
    {
        if (! Schema::hasTable('alimentacao.cardapio_dia')) {
            return [];
        }

        $rows = DB::table('alimentacao.cardapio_dia')->orderBy('data', 'desc')->limit(90)->get();
        if ($rows->isEmpty()) {
            return [];
        }

        $fatorPorEscola = (bool) config('alimentacao.mapa_por_matriculas', false)
            ? $this->matriculasPorEscolaFantasia()
            : [];

        $consolidado = [];
        foreach ($rows as $row) {
            $escola = (string) $row->escola;
            $refs = is_string($row->refeicoes)
                ? (json_decode($row->refeicoes, true) ?: [])
                : (array) $row->refeicoes;

            $fator = max(1, (int) ($fatorPorEscola[$escola] ?? 1));
            $dataRow = (string) ($row->data ?? '');
            foreach ($refs as $ref) {
                $nome = (string) ($ref['refeicao'] ?? 'refeicao');
                $qtd = count($ref['itens'] ?? []) * $fator;
                $k = $escola.'|'.$nome;
                $consolidado[$k]['quantidade'] = ($consolidado[$k]['quantidade'] ?? 0) + $qtd;
                if ($dataRow !== '') {
                    $consolidado[$k]['datas'][$dataRow] = true;
                }
            }
        }

        $out = [];
        foreach ($consolidado as $k => $meta) {
            [$escola, $ref] = explode('|', $k, 2);
            $datas = array_keys($meta['datas'] ?? []);
            rsort($datas);
            $out[] = [
                'escola' => $escola,
                'refeicao' => $ref,
                'quantidade' => (int) ($meta['quantidade'] ?? 0),
                'data' => $datas[0] ?? null,
            ];
        }

        return $out;
    }

    /** @return array<string,int> */
    private function matriculasPorEscolaFantasia(): array
    {
        if (! Schema::hasTable('pmieducar.matricula') || ! Schema::hasTable('pmieducar.escola') || ! Schema::hasTable('cadastro.juridica')) {
            return [];
        }

        try {
            $rows = DB::table('pmieducar.matricula')
                ->join('pmieducar.escola', 'pmieducar.escola.cod_escola', '=', 'pmieducar.matricula.ref_ref_cod_escola')
                ->join('cadastro.juridica', 'cadastro.juridica.idpes', '=', 'pmieducar.escola.ref_idpes')
                ->where('pmieducar.matricula.ativo', 1)
                ->groupBy('cadastro.juridica.fantasia')
                ->selectRaw('cadastro.juridica.fantasia as nome, count(*) as total')
                ->get();
        } catch (\Throwable) {
            return [];
        }

        $map = [];
        foreach ($rows as $r) {
            $map[(string) $r->nome] = max(1, (int) round(((int) $r->total) / 20));
        }

        return $map;
    }

    // ==================== Ficha Técnica (5.2.7, 5.2.8, 5.2.9) ====================

    public function fichasTecnicas(Request $request): View
    {
        $escola = (string) $request->query('escola', '');
        
        return view('alimentacao::fichas-tecnicas', [
            'fichas' => FichaTecnica::listar($escola !== '' ? $escola : null),
            'escola' => $escola,
            'escolas' => $this->escolasDaRede(),
            'podeEditar' => PermissoesAlimentacao::podeEditarItens(),
        ]);
    }

    public function fichaTecnicaForm(Request $request, ?int $id = null): View
    {
        $ficha = $id !== null ? FichaTecnica::buscar($id) : null;
        
        return view('alimentacao::ficha-tecnica-form', [
            'ficha' => $ficha,
            'escolas' => $this->escolasDaRede(),
            'itens' => CatalogoItens::listar(),
            'categorias' => [
                'principal' => 'Prato principal',
                'acompanhamento' => 'Acompanhamento',
                'salada' => 'Salada',
                'sobremesa' => 'Sobremesa',
                'bebida' => 'Bebida',
                'lanche' => 'Lanche',
            ],
        ]);
    }

    public function salvarFichaTecnica(Request $request): RedirectResponse
    {
        if (!PermissoesAlimentacao::podeEditarItens()) {
            return back()->with('erro', 'Sem permissão para gerenciar fichas técnicas.');
        }

        $id = (int) $request->input('id', 0) ?: null;
        
        // Processar ingredientes
        $ingredientes = [];
        $ingredientesNomes = (array) $request->input('ingrediente_nome', []);
        $ingredientesQtd = (array) $request->input('ingrediente_quantidade', []);
        $ingredientesUnidade = (array) $request->input('ingrediente_unidade', []);
        
        $ingredientesItemIds = (array) $request->input('ingrediente_item_id', []);

        foreach ($ingredientesNomes as $idx => $nome) {
            $itemId = (int) ($ingredientesItemIds[$idx] ?? 0) ?: null;
            $nome = trim((string) $nome);
            if ($nome === '' && $itemId) {
                $item = CatalogoItens::encontrar($itemId);
                $nome = $item['nome'] ?? '';
            }
            if ($nome === '' && ! $itemId) {
                continue;
            }

            $ingredientes[] = [
                'nome' => $nome,
                'quantidade' => (float) ($ingredientesQtd[$idx] ?? 0),
                'unidade' => trim((string) ($ingredientesUnidade[$idx] ?? 'g')),
                'item_id' => $itemId,
            ];
        }

        try {
            $novoId = FichaTecnica::salvar(
                $id,
                [
                    'nome' => (string) $request->input('nome', ''),
                    'escola' => (string) $request->input('escola', ''),
                    'categoria' => (string) $request->input('categoria', 'principal'),
                    'modo_preparo' => (string) $request->input('modo_preparo', ''),
                    'utensilios' => (string) $request->input('utensilios', ''),
                    'tempo_preparo_min' => (int) $request->input('tempo_preparo_min', 0),
                    'rendimento_porcoes' => (int) $request->input('rendimento_porcoes', 0),
                    'peso_porcao_g' => (float) $request->input('peso_porcao_g', 0),
                    'ingredientes' => $ingredientes,
                    'ativo' => $request->boolean('ativo', true),
                ],
                Auth::id() ? (int) Auth::id() : null
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }

        AuditoriaFluxo::registrar(null, 'ficha_tecnica', 
            ($id ? 'Atualizada' : 'Criada') . ' ficha técnica: ' . $request->input('nome'));

        return redirect('/alimentacao-escolar/fichas-tecnicas')
            ->with('ok', 'Ficha técnica ' . ($id ? 'atualizada' : 'criada') . ' com sucesso.');
    }

    public function copiarFichaTecnica(Request $request): RedirectResponse
    {
        if (!PermissoesAlimentacao::podeEditarItens()) {
            return back()->with('erro', 'Sem permissão para copiar fichas técnicas.');
        }

        $fichaId = (int) $request->input('ficha_id', 0);
        $escolasDestino = array_values(array_filter((array) $request->input('escolas', [])));

        if ($fichaId <= 0 || $escolasDestino === []) {
            return back()->with('erro', 'Selecione a ficha e ao menos uma escola destino.');
        }

        try {
            $copiadas = FichaTecnica::copiarParaEscolas(
                $fichaId,
                $escolasDestino,
                Auth::id() ? (int) Auth::id() : null
            );
        } catch (\Throwable $e) {
            return back()->with('erro', $e->getMessage());
        }

        AuditoriaFluxo::registrar(null, 'ficha_tecnica', 
            "Ficha #{$fichaId} copiada para {$copiadas} escola(s)");

        return back()->with('ok', "Ficha técnica copiada para {$copiadas} escola(s).");
    }

    public function excluirFichaTecnica(int $id): RedirectResponse
    {
        if (!PermissoesAlimentacao::podeEditarItens()) {
            return back()->with('erro', 'Sem permissão para excluir fichas técnicas.');
        }

        try {
            FichaTecnica::excluir($id);
        } catch (\Throwable $e) {
            return back()->with('erro', $e->getMessage());
        }

        AuditoriaFluxo::registrar(null, 'ficha_tecnica', "Ficha técnica #{$id} excluída");

        return back()->with('ok', 'Ficha técnica excluída.');
    }

    /** Exportação unificada — listagem de fichas técnicas (dados do módulo → geração 4.x). */
    public function exportarFichasTecnicas(Request $request): \Symfony\Component\HttpFoundation\Response|RedirectResponse
    {
        $escola = (string) $request->input('escola', '');
        $fichas = FichaTecnica::listar($escola !== '' ? $escola : null);
        $linhas = [];
        foreach ($fichas as $f) {
            $linhas[] = [
                $f['id'],
                $f['nome'],
                $f['escola'],
                $f['categoria'],
                $f['rendimento_porcoes'],
                $f['kcal_porcao'],
                $f['custo_porcao'],
                count($f['ingredientes'] ?? []),
            ];
        }

        return \iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::download(
            'Fichas técnicas de preparações',
            ['ID', 'Nome', 'Escola', 'Categoria', 'Porções', 'kcal/porção', 'Custo/porção', 'Ingredientes'],
            $linhas,
            'Alimentação escolar',
            (string) $request->input('formato', 'pdf'),
            $escola !== '' ? 'Escola: '.$escola : 'Rede',
            null,
            'alimentacao-fichas',
        );
    }

    /** 5.2.21–24 — sobras limpa/suja/restos + indicadores. */
    public function sobras(Request $request): View|RedirectResponse|\Symfony\Component\HttpFoundation\Response
    {
        $inicio = (string) $request->query('inicio', date('Y-m-01'));
        $fim = (string) $request->query('fim', date('Y-m-d'));
        $escola = trim((string) $request->query('escola', ''));
        $svc = new SobrasRegistro();
        $registros = $svc->listar($escola !== '' ? $escola : null, $inicio, $fim);
        $indicadores = $svc->indicadores($escola !== '' ? $escola : null, $inicio, $fim);

        if ($request->filled('formato') && class_exists(\iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::class)) {
            $linhas = [];
            foreach ($registros as $r) {
                $linhas[] = [
                    $r['data'] ?? '',
                    $r['escola'] ?? '',
                    $r['refeicao'] ?? '',
                    $r['sobra_limpa_kg'] ?? '',
                    $r['sobra_suja_kg'] ?? '',
                    $r['resto_kg'] ?? '',
                    $r['porcoes_produzidas'] ?? '',
                    $r['porcoes_servidas'] ?? '',
                ];
            }

            return \iEducar\Packages\BuritiExportacaoUnificada\Support\ExportacaoModulo::download(
                'Sobras e restos — alimentação',
                ['Data', 'Escola', 'Refeição', 'Sobra limpa kg', 'Sobra suja kg', 'Resto kg', 'Porções prod.', 'Porções serv.'],
                $linhas,
                'Alimentação escolar',
                (string) $request->input('formato', 'pdf'),
                $escola !== '' ? 'Escola: '.$escola : 'Rede · '.$inicio.' a '.$fim,
                null,
                'alimentacao-sobras',
            );
        }

        $escolas = [];
        if (Schema::hasTable('pmieducar.escola')) {
            $escolas = DB::table('pmieducar.escola as e')
                ->join('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
                ->where('e.ativo', 1)
                ->orderBy('j.fantasia')
                ->limit(80)
                ->pluck('j.fantasia')
                ->all();
        }

        return view('alimentacao::sobras', [
            'inicio' => $inicio,
            'fim' => $fim,
            'escola' => $escola,
            'escolas' => $escolas,
            'registros' => $registros,
            'indicadores' => $indicadores,
            'schemaOk' => $svc->disponivel(),
        ]);
    }

    public function salvarSobras(Request $request): RedirectResponse
    {
        $escola = trim((string) $request->input('escola', ''));
        $data = trim((string) $request->input('data', date('Y-m-d')));
        $refeicao = trim((string) $request->input('refeicao', 'almoco'));
        if ($escola === '' || $data === '') {
            return redirect('/alimentacao-escolar/sobras')->with('erro', 'Informe escola e data.');
        }

        try {
            (new SobrasRegistro())->registrar([
                'escola' => $escola,
                'data' => $data,
                'refeicao' => $refeicao !== '' ? $refeicao : 'almoco',
                'sobra_limpa_kg' => $request->input('sobra_limpa_kg') !== null && $request->input('sobra_limpa_kg') !== ''
                    ? (float) $request->input('sobra_limpa_kg') : null,
                'sobra_suja_kg' => $request->input('sobra_suja_kg') !== null && $request->input('sobra_suja_kg') !== ''
                    ? (float) $request->input('sobra_suja_kg') : null,
                'resto_kg' => $request->input('resto_kg') !== null && $request->input('resto_kg') !== ''
                    ? (float) $request->input('resto_kg') : null,
                'porcoes_produzidas' => $request->filled('porcoes_produzidas') ? (int) $request->input('porcoes_produzidas') : null,
                'porcoes_servidas' => $request->filled('porcoes_servidas') ? (int) $request->input('porcoes_servidas') : null,
                'observacao' => trim((string) $request->input('observacao', '')) ?: null,
                'usuario_id' => Auth::id(),
            ]);
            if (class_exists(AuditoriaFluxo::class)) {
                AuditoriaFluxo::registrar(null, 'sobra_registro', 'Registro de sobras/restos · '.$escola.' · '.$data, null, [
                    'escola' => $escola,
                    'refeicao' => $refeicao,
                ]);
            }
        } catch (Throwable $e) {
            report($e);

            return redirect('/alimentacao-escolar/sobras')->with('erro', $e->getMessage());
        }

        return redirect('/alimentacao-escolar/sobras?escola='.urlencode($escola).'&inicio='.date('Y-m-01', strtotime($data)).'&fim='.$data)
            ->with('ok', 'Sobra registrada.');
    }
}
