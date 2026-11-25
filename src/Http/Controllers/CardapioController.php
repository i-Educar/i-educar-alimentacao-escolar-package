<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Http\Controllers;

use iEducar\Packages\BuritiAlimentacaoEscolar\Services\CardapioDia;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\CardapioLote;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\CatalogoItens;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\MapaDistribuicao;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\PnaeRegras;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\RestricaoAlimentar;
use iEducar\Packages\BuritiLagunaAmbiente\Support\AnexoFluxo;
use iEducar\Packages\BuritiLagunaAmbiente\Support\AuditoriaFluxo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
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
        $itensAf = Schema::hasTable('alimentacao.item') && Schema::hasColumn('alimentacao.item', 'agricultura_familiar')
            ? (int) DB::table('alimentacao.item')->where('ativo', true)->where('agricultura_familiar', true)->count()
            : 0;
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
                ['url' => '/alimentacao-escolar/restricoes', 'icone' => 'fa-medkit', 'rotulo' => 'Restrições', 'meta' => 'Laudos'],
                ['url' => '/alimentacao-escolar/mapa', 'icone' => 'fa-map-o', 'rotulo' => 'Mapa', 'meta' => 'Distribuição'],
                ['url' => '/alimentacao-escolar/historico', 'icone' => 'fa-history', 'rotulo' => 'Histórico', 'meta' => 'Auditoria'],
            ],
            'fluxoTitulo' => 'Operação da merenda',
        ]);
    }

    public function cardapio(Request $request): View
    {
        $data = (string) $request->query('data', date('Y-m-d'));
        $escola = (string) $request->query('escola', 'Rede');
        $turma = trim((string) $request->query('turma', ''));
        $turmaId = (int) $request->query('turma_id', 0);
        $cardapio = null;
        $fonte = 'demo';
        $restricoes = [];

        if (Schema::hasTable('alimentacao.cardapio_dia')) {
            $q = DB::table('alimentacao.cardapio_dia')->where('data', $data)->where('escola', $escola);
            if ($turma !== '' && Schema::hasColumn('alimentacao.cardapio_dia', 'turma')) {
                $q->where('turma', $turma);
            } elseif (Schema::hasColumn('alimentacao.cardapio_dia', 'turma')) {
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
                if (! empty($row->turma)) {
                    $turma = (string) $row->turma;
                }
            }
        }

        $cardapio ??= new CardapioDia($data, [
            ['refeicao' => 'almoco', 'itens' => ['arroz', 'feijao', 'fruta']],
        ], $escola);

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

        return view('alimentacao::cardapio', [
            'cardapio' => $cardapio->toArray(),
            'restricoes' => $restricoes,
            'alertas' => $alertas,
            'pnae' => $pnae,
            'fonte' => $fonte,
            'data' => $data,
            'escola' => $escola,
            'turma' => $turma,
            'turma_id' => $turmaId,
            'escolas' => $this->escolasDaRede(),
            'turmas' => $this->turmasDaEscola($escola),
            'catalogo' => CatalogoItens::listar(),
            'podeMontar' => (bool) config('alimentacao.montar_cardapio', true),
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
        $escola = trim((string) $request->input('escola', 'Rede')) ?: 'Rede';
        $turma = trim((string) $request->input('turma', ''));
        $turmaId = (int) $request->input('turma_id', 0);

        $refeicoes = $this->refeicoesDoRequest($request);
        if ($refeicoes === []) {
            return back()->with('erro', 'Informe ao menos uma refeição (café/almoço/lanche).');
        }

        $pnae = PnaeRegras::avaliar($refeicoes);
        if ((bool) config('alimentacao.pnae.bloquear_erro', false)
            && collect($pnae)->contains(static fn (array $a): bool => $a['nivel'] === 'erro')) {
            return back()->withInput()->with('erro', $pnae[0]['mensagem'] ?? 'Cardápio bloqueado por regra PNAE.');
        }

        $dados = [
            'refeicoes' => json_encode($refeicoes, JSON_UNESCAPED_UNICODE),
            'updated_at' => now(),
            'created_at' => now(),
        ];
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'turma')) {
            $dados['turma'] = $turma !== '' ? $turma : null;
        }
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'turma_id')) {
            $dados['turma_id'] = $turmaId > 0 ? $turmaId : null;
        }

        $del = DB::table('alimentacao.cardapio_dia')->where('data', $data)->where('escola', $escola);
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'turma')) {
            if ($turma !== '') {
                $del->where('turma', $turma);
            } else {
                $del->where(function ($w) {
                    $w->whereNull('turma')->orWhere('turma', '');
                });
            }
        }
        $del->delete();
        DB::table('alimentacao.cardapio_dia')->insert(array_merge(
            ['data' => $data, 'escola' => $escola],
            $dados
        ));

        $qs = http_build_query(array_filter([
            'data' => $data,
            'escola' => $escola,
            'turma' => $turma !== '' ? $turma : null,
            'turma_id' => $turmaId > 0 ? $turmaId : null,
        ]));

        AuditoriaFluxo::registrar(
            null,
            'cardapio',
            "Cardápio {$escola}".($turma !== '' ? " / {$turma}" : '')." em {$data}",
            null,
            ['escola' => $escola, 'data' => $data, 'turma' => $turma, 'refeicoes' => $refeicoes]
        );

        $avisoPnae = collect($pnae)->first(static fn (array $a): bool => in_array($a['nivel'], ['erro', 'alerta'], true));
        $redirect = redirect('/alimentacao-escolar/cardapio?'.$qs)
            ->with('ok', 'Cardápio de '.$escola.($turma !== '' ? ' / '.$turma : '')." salvo para {$data}.");

        return $avisoPnae
            ? $redirect->with('alerta', $avisoPnae['mensagem'])
            : $redirect;
    }

    public function cardapioLote(): View
    {
        return view('alimentacao::cardapio-lote', [
            'escolas' => $this->escolasDaRede(),
            'catalogo' => CatalogoItens::listar(),
            'data_inicio' => date('Y-m-d'),
            'data_fim' => date('Y-m-d', strtotime('+4 weekdays')),
            'podeMontar' => (bool) config('alimentacao.montar_cardapio', true),
        ]);
    }

    public function salvarCardapioLote(Request $request): RedirectResponse
    {
        if (! (bool) config('alimentacao.montar_cardapio', true)) {
            return back()->with('erro', 'Montagem de cardápio desabilitada por config.');
        }

        $refeicoes = $this->refeicoesDoRequest($request);
        $escolas = array_values(array_filter((array) $request->input('escolas', [])));
        if ($escolas === [] && $request->filled('escola')) {
            $escolas = [trim((string) $request->input('escola'))];
        }

        try {
            $resultado = CardapioLote::gravar(
                (string) $request->input('data_inicio', date('Y-m-d')),
                (string) $request->input('data_fim', date('Y-m-d')),
                $escolas,
                $refeicoes,
                trim((string) $request->input('turma', '')),
                (int) $request->input('turma_id', 0) ?: null
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }

        $aviso = collect($resultado['pnae'])->first(static fn (array $a): bool => in_array($a['nivel'], ['erro', 'alerta'], true));
        $redirect = redirect('/alimentacao-escolar/cardapio-lote')
            ->with('ok', $resultado['gravados'].' cardápio(s) gravado(s) em lote.');

        return $aviso ? $redirect->with('alerta', $aviso['mensagem']) : $redirect;
    }

    public function itens(): View
    {
        return view('alimentacao::itens', [
            'itens' => CatalogoItens::listar(false),
            'podeMontar' => (bool) config('alimentacao.montar_cardapio', true),
        ]);
    }

    public function salvarItem(Request $request): RedirectResponse
    {
        if (! (bool) config('alimentacao.montar_cardapio', true)) {
            return back()->with('erro', 'Cadastro de itens desabilitado por config.');
        }

        try {
            CatalogoItens::salvar(
                (int) $request->input('id', 0) ?: null,
                [
                    'nome' => (string) $request->input('nome', ''),
                    'categoria' => (string) $request->input('categoria', 'in_natura'),
                    'grupo_pnae' => (string) $request->input('grupo_pnae', 'outro'),
                    'origem_af' => $request->boolean('origem_af'),
                    'ativo' => $request->boolean('ativo', true),
                    'unidade' => (string) $request->input('unidade', 'porção'),
                ]
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('erro', $e->getMessage());
        }

        return redirect('/alimentacao-escolar/itens')->with('ok', 'Item do catálogo PNAE salvo.');
    }

    /**
     * @return list<array{refeicao:string,itens:list<string>}>
     */
    private function refeicoesDoRequest(Request $request): array
    {
        $refeicoes = [];
        foreach (['cafe' => 'cafe', 'almoco' => 'almoco', 'lanche' => 'lanche'] as $campo => $rotulo) {
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
            if ($itens !== []) {
                $refeicoes[] = ['refeicao' => $rotulo, 'itens' => $itens];
            }
        }

        return $refeicoes;
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
                ->whereIn('acao', ['cardapio', 'restricao', 'item'])
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
                ['escola' => 'Rede', 'refeicao' => 'almoco', 'quantidade' => 120],
                ['escola' => 'EMEF Norte', 'refeicao' => 'almoco', 'quantidade' => 80],
                ['escola' => 'EMEF Sul', 'refeicao' => 'cafe', 'quantidade' => 95],
            ];
        }

        $mapa = MapaDistribuicao::consolidar($linhas);

        return view('alimentacao::mapa', [
            'mapa' => $mapa,
            'fonte' => $fonte,
            'porMatriculas' => (bool) config('alimentacao.mapa_por_matriculas', false),
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
            foreach ($refs as $ref) {
                $nome = (string) ($ref['refeicao'] ?? 'refeicao');
                $qtd = count($ref['itens'] ?? []) * $fator;
                $k = $escola.'|'.$nome;
                $consolidado[$k] = ($consolidado[$k] ?? 0) + $qtd;
            }
        }

        $out = [];
        foreach ($consolidado as $k => $qtd) {
            [$escola, $ref] = explode('|', $k, 2);
            $out[] = ['escola' => $escola, 'refeicao' => $ref, 'quantidade' => (int) $qtd];
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
}
