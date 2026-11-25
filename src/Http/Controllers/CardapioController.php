<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Http\Controllers;

use iEducar\Packages\BuritiAlimentacaoEscolar\Services\CardapioDia;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\RestricaoAlimentar;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class CardapioController
{
    public function index(): View
    {
        return view('alimentacao::inicio');
    }

    public function cardapio(Request $request): View
    {
        $data = (string) $request->query('data', date('Y-m-d'));
        $escola = (string) $request->query('escola', 'Rede');
        $cardapio = null;
        $restricoes = [];
        $fonte = 'demo';

        if (Schema::hasTable('alimentacao.cardapio_dia')) {
            $row = DB::table('alimentacao.cardapio_dia')
                ->where('data', $data)
                ->where('escola', $escola)
                ->first();
            if ($row === null) {
                $row = DB::table('alimentacao.cardapio_dia')->where('data', $data)->orderBy('id')->first();
            }
            if ($row !== null) {
                $refeicoes = is_string($row->refeicoes)
                    ? (json_decode($row->refeicoes, true) ?: [])
                    : (array) $row->refeicoes;
                $cardapio = new CardapioDia((string) $row->data, $refeicoes, (string) $row->escola);
                $fonte = 'alimentacao.cardapio_dia';
            }
        }

        if ($cardapio === null) {
            $cardapio = new CardapioDia($data, [
                ['refeicao' => 'almoco', 'itens' => ['arroz', 'feijao', 'fruta']],
            ], $escola);
        }

        if (Schema::hasTable('alimentacao.restricao_aluno')) {
            $restricoes = DB::table('alimentacao.restricao_aluno')
                ->orderBy('aluno_id')
                ->limit(50)
                ->get(['aluno_id', 'restricao'])
                ->map(static fn ($r): array => [
                    'aluno_id' => (int) $r->aluno_id,
                    'restricao' => (string) $r->restricao,
                ])
                ->all();
        }

        $alertas = [];
        foreach ($restricoes as $r) {
            $svc = new RestricaoAlimentar((int) $r['aluno_id'], [(string) $r['restricao']]);
            if ($svc->conflita($cardapio->toArray()['refeicoes'][0]['itens'] ?? [])) {
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
}
