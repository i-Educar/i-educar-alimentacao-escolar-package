<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class PerfilEscolar
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function listar(string $data, ?string $escola = null): array
    {
        if (! Schema::hasTable('alimentacao.perfil_servico')) {
            return [];
        }

        $q = DB::table('alimentacao.perfil_servico')->where('data', $data)->orderBy('escola')->orderBy('refeicao');
        if ($escola !== null && $escola !== '' && $escola !== 'Rede') {
            $q->where('escola', $escola);
        }

        return $q->get()->map(static function ($r): array {
            $segmentos = [];
            if (! empty($r->segmentos_json ?? null)) {
                $decoded = json_decode((string) $r->segmentos_json, true);
                if (is_array($decoded)) {
                    $segmentos = array_values(array_map('strval', $decoded));
                }
            }
            if ($segmentos === [] && ! empty($r->segmento)) {
                $segmentos = [(string) $r->segmento];
            }

            return [
                'id' => (int) $r->id,
                'data' => (string) $r->data,
                'escola' => (string) $r->escola,
                'segmento' => $r->segmento !== null ? (string) $r->segmento : ($segmentos[0] ?? null),
                'segmentos' => $segmentos,
                'turma' => $r->turma !== null ? (string) $r->turma : null,
                'cardapio_id' => isset($r->cardapio_id) ? (int) $r->cardapio_id : null,
                'refeicao' => (string) $r->refeicao,
                'porcoes_previstas' => (int) $r->porcoes_previstas,
                'porcoes_servidas' => (int) $r->porcoes_servidas,
                'estimado' => (bool) ($r->estimado ?? false),
                'origem' => isset($r->origem) ? (string) $r->origem : null,
                'observacao' => $r->observacao !== null ? (string) $r->observacao : null,
                'confirmado_em' => $r->confirmado_em !== null ? (string) $r->confirmado_em : null,
            ];
        })->all();
    }

    /**
     * @param  array{data:string,escola:string,refeicao:string,porcoes_previstas?:int,porcoes_servidas?:int,segmento?:?string,segmentos?:list<string>,turma?:?string,cardapio_id?:?int,observacao?:?string,confirmar?:bool,usuario_id?:?int,estimado?:bool,origem?:?string,somente_fatos?:bool}  $dados
     */
    public static function registrar(array $dados): int
    {
        if (! Schema::hasTable('alimentacao.perfil_servico')) {
            throw new \RuntimeException('Tabela alimentacao.perfil_servico ausente — rode migrate.');
        }

        $data = (string) ($dados['data'] ?? '');
        $escola = trim((string) ($dados['escola'] ?? ''));
        $refeicao = mb_strtolower(trim((string) ($dados['refeicao'] ?? '')));
        if ($data === '' || $escola === '' || $refeicao === '') {
            throw new \InvalidArgumentException('Data, escola e refeição são obrigatórios.');
        }

        $segmentos = [];
        if (! empty($dados['segmentos']) && is_array($dados['segmentos'])) {
            $segmentos = array_values(array_filter(array_map(
                static fn ($s): string => trim((string) $s),
                $dados['segmentos']
            )));
        } elseif (isset($dados['segmento']) && trim((string) $dados['segmento']) !== '') {
            $segmentos = [trim((string) $dados['segmento'])];
        }
        $segmento = $segmentos[0] ?? null;
        $turma = isset($dados['turma']) && trim((string) $dados['turma']) !== ''
            ? trim((string) $dados['turma']) : null;

        $somenteFatos = ! empty($dados['somente_fatos'])
            || (bool) ConfigAlimentacao::get('unidade_so_registra_fatos', true);

        $servidas = max(0, (int) ($dados['porcoes_servidas'] ?? 0));
        $previstas = max(0, (int) ($dados['porcoes_previstas'] ?? 0));
        if ($previstas <= 0 || (! empty($dados['estimado']) && empty($dados['porcoes_previstas']))) {
            $previstas = EstimativaPorcoes::estimar($escola, $segmentos, $turma);
        }

        $chave = [
            'data' => $data,
            'escola' => $escola,
            'refeicao' => $refeicao,
            'turma' => $turma,
            'segmento' => $segmento,
        ];

        $existente = DB::table('alimentacao.perfil_servico')->where($chave)->first();

        $payload = [
            'porcoes_servidas' => $servidas,
            'cardapio_id' => isset($dados['cardapio_id']) ? (int) $dados['cardapio_id'] : null,
            'observacao' => isset($dados['observacao']) ? trim((string) $dados['observacao']) : null,
            'updated_at' => now(),
        ];

        if (Schema::hasColumn('alimentacao.perfil_servico', 'segmentos_json')) {
            $payload['segmentos_json'] = $segmentos !== []
                ? json_encode($segmentos, JSON_UNESCAPED_UNICODE)
                : null;
        }
        if (Schema::hasColumn('alimentacao.perfil_servico', 'origem')) {
            $payload['origem'] = (string) ($dados['origem'] ?? ($existente->origem ?? 'manual'));
        }
        if (Schema::hasColumn('alimentacao.perfil_servico', 'estimado')) {
            $payload['estimado'] = (bool) ($dados['estimado'] ?? ($existente->estimado ?? false));
        }

        // Unidade só registra fatos: não sobrescreve previstas já estimadas
        if ($somenteFatos && $existente !== null && (int) $existente->porcoes_previstas > 0) {
            // mantém previstas
        } else {
            $payload['porcoes_previstas'] = $previstas;
            if (Schema::hasColumn('alimentacao.perfil_servico', 'estimado') && empty($dados['porcoes_previstas'])) {
                $payload['estimado'] = true;
            }
        }

        if (! empty($dados['confirmar'])) {
            $payload['confirmado_em'] = now();
            $payload['confirmado_por'] = isset($dados['usuario_id']) ? (int) $dados['usuario_id'] : null;
        }

        if ($existente !== null) {
            DB::table('alimentacao.perfil_servico')->where('id', $existente->id)->update($payload);

            return (int) $existente->id;
        }

        $payload['porcoes_previstas'] = $previstas;
        $payload['created_at'] = now();
        if (Schema::hasColumn('alimentacao.perfil_servico', 'estimado')) {
            $payload['estimado'] = true;
        }

        return (int) DB::table('alimentacao.perfil_servico')->insertGetId(array_merge($chave, $payload));
    }

    /**
     * Garante linhas estimadas por refeição do cardápio (automação).
     *
     * @param  list<array{refeicao:string}>  $refeicoes
     * @param  list<string>  $segmentos
     */
    public static function garantirEstimativas(
        string $data,
        string $escola,
        array $refeicoes,
        array $segmentos = [],
        ?string $turma = null,
        ?int $usuarioId = null,
    ): void {
        if (! (bool) ConfigAlimentacao::get('estimar_porcoes_ao_lancar', true)) {
            return;
        }
        if ($escola === '' || $escola === 'Rede') {
            return;
        }

        $previstas = EstimativaPorcoes::estimar($escola, $segmentos, $turma);
        foreach ($refeicoes as $ref) {
            $refeicao = mb_strtolower((string) ($ref['refeicao'] ?? ''));
            if ($refeicao === '') {
                continue;
            }
            try {
                self::registrar([
                    'data' => $data,
                    'escola' => $escola,
                    'refeicao' => $refeicao,
                    'porcoes_previstas' => $previstas,
                    'porcoes_servidas' => 0,
                    'segmentos' => $segmentos,
                    'turma' => $turma,
                    'observacao' => 'Estimativa automática ao lançar cardápio',
                    'estimado' => true,
                    'origem' => 'cardapio',
                    'somente_fatos' => false,
                    'usuario_id' => $usuarioId,
                ]);
            } catch (\Throwable) {
            }
        }
    }
}
