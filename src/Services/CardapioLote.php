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
     * @return array{gravados:int,pnae:list<array{nivel:string,codigo:string,mensagem:string}>}
     */
    public static function gravar(
        string $dataInicio,
        string $dataFim,
        array $escolas,
        array $refeicoes,
        string $turma = '',
        ?int $turmaId = null
    ): array {
        if (! Schema::hasTable('alimentacao.cardapio_dia')) {
            throw new \RuntimeException('Schema alimentacao.cardapio_dia ausente.');
        }
        if ($refeicoes === []) {
            throw new \InvalidArgumentException('Informe ao menos uma refeição.');
        }

        $escolas = array_values(array_filter(array_map('trim', $escolas), static fn (string $e): bool => $e !== ''));
        if ($escolas === []) {
            $escolas = ['Rede'];
        }

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
            // pula fins de semana se configurado
            if ((bool) config('alimentacao.lote_somente_uteis', true) && in_array((int) $d->format('N'), [6, 7], true)) {
                continue;
            }
            $data = $d->format('Y-m-d');
            foreach ($escolas as $escola) {
                self::upsertDia($data, $escola, $turma, $turmaId, $json);
                $gravados++;
            }
        }

        return ['gravados' => $gravados, 'pnae' => $pnae];
    }

    private static function upsertDia(string $data, string $escola, string $turma, ?int $turmaId, string $json): void
    {
        $del = DB::table('alimentacao.cardapio_dia')->where('data', $data)->where('escola', $escola);
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

        DB::table('alimentacao.cardapio_dia')->insert($dados);
    }
}
