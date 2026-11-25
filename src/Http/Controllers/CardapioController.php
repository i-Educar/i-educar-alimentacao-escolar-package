<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Http\Controllers;

use iEducar\Packages\BuritiAlimentacaoEscolar\Services\CardapioDia;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\MapaDistribuicao;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\RestricaoAlimentar;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CardapioController
{
    public function index(): View
    {
        $temSchema = Schema::hasTable('alimentacao.cardapio_dia');
        $totalCardapios = $temSchema ? (int) DB::table('alimentacao.cardapio_dia')->count() : 0;
        $totalRestricoes = Schema::hasTable('alimentacao.restricao_aluno')
            ? (int) DB::table('alimentacao.restricao_aluno')->count()
            : 0;

        return view('alimentacao::painel', compact('temSchema', 'totalCardapios', 'totalRestricoes'));
    }

    public function cardapio(Request $request): View
    {
        $data = (string) $request->query('data', date('Y-m-d'));
        $escola = (string) $request->query('escola', 'Rede');
        $cardapio = null;
        $fonte = 'demo';
        $restricoes = [];

        if (Schema::hasTable('alimentacao.cardapio_dia')) {
            $row = DB::table('alimentacao.cardapio_dia')
                ->where('data', $data)
                ->where('escola', $escola)
                ->first()
                ?? DB::table('alimentacao.cardapio_dia')->where('data', $data)->orderBy('id')->first();

            if ($row !== null) {
                $refeicoes = is_string($row->refeicoes)
                    ? (json_decode($row->refeicoes, true) ?: [])
                    : (array) $row->refeicoes;
                $cardapio = new CardapioDia((string) $row->data, $refeicoes, (string) $row->escola);
                $fonte = 'alimentacao.cardapio_dia';
            }
        }

        $cardapio ??= new CardapioDia($data, [
            ['refeicao' => 'almoco', 'itens' => ['arroz', 'feijao', 'fruta']],
        ], $escola);

        if (Schema::hasTable('alimentacao.restricao_aluno')) {
            $restricoes = DB::table('alimentacao.restricao_aluno')
                ->orderBy('ref_cod_aluno')
                ->limit(50)
                ->get()
                ->map(static fn ($r): array => [
                    'aluno_id' => (int) ($r->ref_cod_aluno ?? $r->aluno_id ?? 0),
                    'restricao' => (string) $r->restricao,
                ])->all();
        }

        $alertas = [];
        $itens = [];
        foreach ($cardapio->toArray()['refeicoes'] ?? [] as $ref) {
            $itens = array_merge($itens, $ref['itens'] ?? []);
        }
        foreach ($restricoes as $r) {
            $svc = new RestricaoAlimentar((int) $r['aluno_id'], [(string) $r['restricao']]);
            if ($svc->conflita($itens)) {
                $alertas[] = $r;
            }
        }

        return view('alimentacao::cardapio', [
            'cardapio' => $cardapio->toArray(),
            'restricoes' => $restricoes,
            'alertas' => $alertas,
            'fonte' => $fonte,
            'data' => $data,
            'escola' => $escola,
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

        return view('alimentacao::restricoes', compact('linhas'));
    }

    public function mapa(): View
    {
        $mapa = MapaDistribuicao::consolidar([
            ['escola' => 'Rede', 'refeicao' => 'almoco', 'quantidade' => 120],
            ['escola' => 'EMEF Norte', 'refeicao' => 'almoco', 'quantidade' => 80],
            ['escola' => 'EMEF Sul', 'refeicao' => 'cafe', 'quantidade' => 95],
        ]);

        return view('alimentacao::mapa', compact('mapa'));
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
}
