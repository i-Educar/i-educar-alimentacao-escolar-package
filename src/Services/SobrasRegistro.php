<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 5.2.21–24 — Registro de sobras/restos e indicadores de desperdício.
 */
final class SobrasRegistro
{
    public function disponivel(): bool
    {
        return Schema::hasTable('alimentacao.sobra_registro');
    }

    /**
     * @return list<array<string,mixed>>
     */
    public function listar(?string $escola, string $inicio, string $fim, int $limite = 200): array
    {
        if (! $this->disponivel()) {
            return [];
        }

        $q = DB::table('alimentacao.sobra_registro')
            ->whereBetween('data', [$inicio, $fim])
            ->orderByDesc('data')
            ->orderByDesc('id')
            ->limit($limite);

        if ($escola !== null && $escola !== '') {
            $q->where('escola', $escola);
        }

        return $q->get()->map(static fn ($r) => (array) $r)->all();
    }

    /**
     * @param  array{
     *   escola:string,data:string,refeicao:string,
     *   sobra_limpa_kg?:float|null,sobra_suja_kg?:float|null,resto_kg?:float|null,
     *   porcoes_produzidas?:int|null,porcoes_servidas?:int|null,observacao?:string|null,usuario_id?:int|null
     * }  $dados
     */
    public function registrar(array $dados): int
    {
        if (! $this->disponivel()) {
            throw new \RuntimeException('Tabela alimentacao.sobra_registro ausente. Rode as migrations.');
        }

        return (int) DB::table('alimentacao.sobra_registro')->insertGetId([
            'escola' => (string) $dados['escola'],
            'data' => (string) $dados['data'],
            'refeicao' => (string) ($dados['refeicao'] ?? 'almoco'),
            'sobra_limpa_kg' => $dados['sobra_limpa_kg'] ?? null,
            'sobra_suja_kg' => $dados['sobra_suja_kg'] ?? null,
            'resto_kg' => $dados['resto_kg'] ?? null,
            'porcoes_produzidas' => $dados['porcoes_produzidas'] ?? null,
            'porcoes_servidas' => $dados['porcoes_servidas'] ?? null,
            'observacao' => $dados['observacao'] ?? null,
            'usuario_id' => $dados['usuario_id'] ?? null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * @return array{
     *   total_registros:int,
     *   sobra_limpa_kg:float,
     *   sobra_suja_kg:float,
     *   resto_kg:float,
     *   porcoes_produzidas:int,
     *   porcoes_servidas:int,
     *   desperdicio_pct:?float,
     *   por_refeicao:array<string,array{limpa:float,suja:float,resto:float}>
     * }
     */
    public function indicadores(?string $escola, string $inicio, string $fim): array
    {
        $rows = $this->listar($escola, $inicio, $fim, 5000);
        $out = [
            'total_registros' => count($rows),
            'sobra_limpa_kg' => 0.0,
            'sobra_suja_kg' => 0.0,
            'resto_kg' => 0.0,
            'porcoes_produzidas' => 0,
            'porcoes_servidas' => 0,
            'desperdicio_pct' => null,
            'por_refeicao' => [],
        ];

        foreach ($rows as $r) {
            $limpa = (float) ($r['sobra_limpa_kg'] ?? 0);
            $suja = (float) ($r['sobra_suja_kg'] ?? 0);
            $resto = (float) ($r['resto_kg'] ?? 0);
            $out['sobra_limpa_kg'] += $limpa;
            $out['sobra_suja_kg'] += $suja;
            $out['resto_kg'] += $resto;
            $out['porcoes_produzidas'] += (int) ($r['porcoes_produzidas'] ?? 0);
            $out['porcoes_servidas'] += (int) ($r['porcoes_servidas'] ?? 0);
            $ref = (string) ($r['refeicao'] ?? 'outra');
            if (! isset($out['por_refeicao'][$ref])) {
                $out['por_refeicao'][$ref] = ['limpa' => 0.0, 'suja' => 0.0, 'resto' => 0.0];
            }
            $out['por_refeicao'][$ref]['limpa'] += $limpa;
            $out['por_refeicao'][$ref]['suja'] += $suja;
            $out['por_refeicao'][$ref]['resto'] += $resto;
        }

        $prod = $out['porcoes_produzidas'];
        $serv = $out['porcoes_servidas'];
        if ($prod > 0) {
            $out['desperdicio_pct'] = round(100 * max(0, $prod - $serv) / $prod, 1);
        }

        return $out;
    }
}
