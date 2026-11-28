<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CardapioLote
{
    /**
     * Replica o mesmo cardápio para N escolas e/ou intervalo de datas.
     *
     * @param  list<string>  $escolas
     * @param  list<array{refeicao:string,itens:list<string>}>  $refeicoes
     * @param  list<array{nome:string,id:int}>|string  $turmas  lista ou turma legada
     * @param  list<string>|string|null  $segmentos  lista ou segmento legado
     * @return array{gravados:int,pnae:list<array{nivel:string,codigo:string,mensagem:string}>}
     */
    public static function gravar(
        string $dataInicio,
        string $dataFim,
        array $escolas,
        array $refeicoes,
        string|array $turmas = '',
        ?int $turmaId = null,
        string $escopo = EscopoCardapio::ESCOLA,
        string|array|null $segmentos = null,
        string $periodo = 'semanal',
        bool $confirmar = false,
        ?int $usuarioId = null,
    ): array {
        if (! Schema::hasTable('alimentacao.cardapio_dia')) {
            throw new \RuntimeException('Schema alimentacao.cardapio_dia ausente.');
        }
        if ($refeicoes === []) {
            $refeicoes = BandejasAutomaticas::montar();
        }
        if ($refeicoes === []) {
            throw new \InvalidArgumentException('Informe ao menos uma refeição ou cadastre itens no catálogo.');
        }

        $escopo = EscopoCardapio::normalizar($escopo);
        $escolas = array_values(array_filter(array_map('trim', $escolas), static fn (string $e): bool => $e !== ''));
        if ($escopo === EscopoCardapio::REDE || $escolas === []) {
            $escolas = ['Rede'];
        }

        $turmasNorm = is_array($turmas)
            ? EscopoCardapio::normalizarTurmas($turmas)
            : EscopoCardapio::normalizarTurmas([], $turmas, $turmaId ?? 0);
        $segsNorm = EscopoCardapio::normalizarSegmentos(
            is_array($segmentos) ? $segmentos : [],
            is_string($segmentos) ? $segmentos : null
        );

        $inicio = new \DateTimeImmutable($dataInicio);
        $fim = new \DateTimeImmutable($dataFim);
        if ($fim < $inicio) {
            [$inicio, $fim] = [$fim, $inicio];
        }

        $pnae = PnaeRegras::avaliar($refeicoes);
        $bloqueia = collect($pnae)->contains(static fn (array $a): bool => $a['nivel'] === 'erro'
            && (bool) config('alimentacao.pnae.bloquear_erro', false));
        if ($bloqueia) {
            throw new \InvalidArgumentException('Cardápio bloqueado por regra PNAE: '.$pnae[0]['mensagem']);
        }

        $json = json_encode($refeicoes, JSON_UNESCAPED_UNICODE);
        $gravados = 0;
        for ($d = $inicio; $d <= $fim; $d = $d->modify('+1 day')) {
            if ((bool) config('alimentacao.lote_somente_uteis', true) && in_array((int) $d->format('N'), [6, 7], true)) {
                continue;
            }
            $data = $d->format('Y-m-d');
            foreach ($escolas as $escola) {
                $expandido = EscopoCardapio::expandirDestinos($escopo, $escola, $turmasNorm, $segsNorm);
                if (! $expandido['ok']) {
                    throw new \InvalidArgumentException((string) $expandido['erro']);
                }
                foreach ($expandido['destinos'] as $destino) {
                    self::upsertDia(
                        $data,
                        $destino['escola'],
                        $destino['turma'],
                        $destino['turma_id'] > 0 ? $destino['turma_id'] : null,
                        $json,
                        $escopo,
                        $destino['segmento'],
                        $periodo,
                        $confirmar,
                        $usuarioId
                    );
                    $gravados++;
                }
            }
        }

        return ['gravados' => $gravados, 'pnae' => $pnae];
    }

    /**
     * @param  list<array{refeicao:string,itens:list<string>}>|string  $refeicoesJson
     */
    public static function upsertDiaPublico(
        string $data,
        string $escola,
        string $turma,
        ?int $turmaId,
        string $json,
        string $escopo = EscopoCardapio::ESCOLA,
        ?string $segmento = null,
        string $periodo = 'diario',
        bool $confirmar = false,
        ?int $usuarioId = null,
    ): void {
        self::upsertDia($data, $escola, $turma, $turmaId, $json, $escopo, $segmento, $periodo, $confirmar, $usuarioId);
    }

    private static function upsertDia(
        string $data,
        string $escola,
        string $turma,
        ?int $turmaId,
        string $json,
        string $escopo = EscopoCardapio::ESCOLA,
        ?string $segmento = null,
        string $periodo = 'diario',
        bool $confirmar = false,
        ?int $usuarioId = null,
    ): void {
        $del = DB::table('alimentacao.cardapio_dia')->where('data', $data)->where('escola', $escola);
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'escopo')) {
            $del->where('escopo', $escopo);
        }
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'segmento')) {
            if ($segmento) {
                $del->where('segmento', $segmento);
            } else {
                $del->where(function ($w): void {
                    $w->whereNull('segmento')->orWhere('segmento', '');
                });
            }
        }
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'turma')) {
            if ($turma !== '') {
                $del->where('turma', $turma);
            } else {
                $del->where(function ($w): void {
                    $w->whereNull('turma')->orWhere('turma', '');
                });
            }
        }
        $del->delete();

        $dados = [
            'data' => $data,
            'escola' => $escola,
            'refeicoes' => $json,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'turma')) {
            $dados['turma'] = $turma !== '' ? $turma : null;
        }
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'turma_id')) {
            $dados['turma_id'] = ($turmaId !== null && $turmaId > 0) ? $turmaId : null;
        }
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'escopo')) {
            $dados['escopo'] = $escopo;
        }
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'segmento')) {
            $dados['segmento'] = $segmento;
        }
        if (Schema::hasColumn('alimentacao.cardapio_dia', 'periodo')) {
            $dados['periodo'] = $periodo;
        }
        if ($confirmar && Schema::hasColumn('alimentacao.cardapio_dia', 'confirmado_em')) {
            $dados['confirmado_em'] = now();
            $dados['confirmado_por'] = $usuarioId;
        }

        DB::table('alimentacao.cardapio_dia')->insert($dados);
    }
}
