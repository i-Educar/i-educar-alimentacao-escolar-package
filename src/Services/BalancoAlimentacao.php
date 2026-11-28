<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class BalancoAlimentacao
{
    /**
     * Consolida consumo, custo previsto/real e incongruências no período.
     *
     * @return array{
     *   periodo:array{inicio:string,fim:string},
     *   totais:array{previstas:int,servidas:int,custo_previsto:float,custo_servido:float,kcal_previsto:float,kcal_servido:float},
     *   itens:list<array<string,mixed>>,
     *   incongruencias:list<array{nivel:string,codigo:string,mensagem:string}>,
     *   por_escola:list<array<string,mixed>>,
     *   adesao:array{percentual:float,porcoes_servidas:int,matriculas:int,por_escola:list<array>}
     * }
     */
    public static function gerar(string $inicio, string $fim, ?string $escola = null): array
    {
        $catalogo = [];
        foreach (CatalogoItens::listar(false) as $item) {
            $catalogo[mb_strtolower($item['nome'])] = $item;
        }

        $cardapios = [];
        if (Schema::hasTable('alimentacao.cardapio_dia')) {
            $q = DB::table('alimentacao.cardapio_dia')
                ->whereBetween('data', [$inicio, $fim])
                ->orderBy('data');
            if ($escola !== null && $escola !== '' && $escola !== 'Rede') {
                $q->where(function ($w) use ($escola): void {
                    $w->where('escola', $escola)->orWhere('escola', 'Rede');
                });
            }
            $cardapios = $q->get()->all();
        }

        $servicos = [];
        if (Schema::hasTable('alimentacao.perfil_servico')) {
            $q = DB::table('alimentacao.perfil_servico')
                ->whereBetween('data', [$inicio, $fim])
                ->orderBy('data');
            if ($escola !== null && $escola !== '' && $escola !== 'Rede') {
                $q->where('escola', $escola);
            }
            $servicos = $q->get()->all();
        }

        $porItem = [];
        $porEscola = [];
        $incongruencias = [];
        $totPrev = 0;
        $totServ = 0;
        $custoPrev = 0.0;
        $custoServ = 0.0;
        $kcalPrev = 0.0;
        $kcalServ = 0.0;

        foreach ($cardapios as $row) {
            $refeicoes = is_string($row->refeicoes)
                ? (json_decode($row->refeicoes, true) ?: [])
                : (array) $row->refeicoes;
            $escolaRow = (string) $row->escola;
            $fatorServico = self::fatorServico($servicos, (string) $row->data, $escolaRow);

            foreach ($refeicoes as $ref) {
                $itens = $ref['itens'] ?? [];
                $porcoes = $ref['porcoes'] ?? [];
                $mapaPorcao = [];
                foreach ($porcoes as $p) {
                    $nome = mb_strtolower(trim((string) ($p['item'] ?? '')));
                    if ($nome !== '') {
                        $mapaPorcao[$nome] = $p;
                    }
                }

                foreach ($itens as $nomeItem) {
                    $chave = mb_strtolower(trim((string) $nomeItem));
                    if ($chave === '') {
                        continue;
                    }
                    $meta = $catalogo[$chave] ?? null;
                    $peso = (float) ($mapaPorcao[$chave]['peso_g'] ?? ($meta['peso_porcao_g'] ?? 0));
                    $kcal = (float) ($mapaPorcao[$chave]['kcal'] ?? ($meta['kcal_porcao'] ?? 0));
                    $custo = (float) ($meta['custo_unitario'] ?? 0);
                    $previstas = max(1, (int) ($fatorServico['previstas'] ?? 1));
                    $servidas = (int) ($fatorServico['servidas'] ?? 0);

                    if (! isset($porItem[$chave])) {
                        $porItem[$chave] = [
                            'item' => (string) $nomeItem,
                            'peso_g' => $peso,
                            'kcal' => $kcal,
                            'custo_unitario' => $custo,
                            'porcoes_previstas' => 0,
                            'porcoes_servidas' => 0,
                            'peso_previsto_kg' => 0.0,
                            'peso_servido_kg' => 0.0,
                            'custo_previsto' => 0.0,
                            'custo_servido' => 0.0,
                            'fora_padrao' => $meta !== null ? empty($meta['padrao_pnae']) : true,
                            'sem_nutricao' => $meta === null || ($meta['peso_porcao_g'] ?? null) === null,
                        ];
                    }

                    $porItem[$chave]['porcoes_previstas'] += $previstas;
                    $porItem[$chave]['porcoes_servidas'] += $servidas;
                    $porItem[$chave]['peso_previsto_kg'] += ($peso * $previstas) / 1000;
                    $porItem[$chave]['peso_servido_kg'] += ($peso * $servidas) / 1000;
                    $porItem[$chave]['custo_previsto'] += $custo * $previstas;
                    $porItem[$chave]['custo_servido'] += $custo * $servidas;

                    $totPrev += $previstas;
                    $totServ += $servidas;
                    $custoPrev += $custo * $previstas;
                    $custoServ += $custo * $servidas;
                    $kcalPrev += $kcal * $previstas;
                    $kcalServ += $kcal * $servidas;

                    if ($meta === null) {
                        $incongruencias[] = [
                            'nivel' => 'alerta',
                            'codigo' => 'item_fora_catalogo',
                            'mensagem' => "Item '{$nomeItem}' no cardápio de {$escolaRow} ({$row->data}) fora do catálogo PNAE.",
                        ];
                    } elseif (empty($meta['padrao_pnae'])) {
                        $incongruencias[] = [
                            'nivel' => 'alerta',
                            'codigo' => 'fora_padrao',
                            'mensagem' => "Item '{$nomeItem}' marcado fora do padrão PNAE ({$escolaRow}, {$row->data}).",
                        ];
                    } elseif (($meta['categoria'] ?? '') === 'ultraprocessado') {
                        $incongruencias[] = [
                            'nivel' => 'alerta',
                            'codigo' => 'ultraprocessado',
                            'mensagem' => "Ultraprocessado '{$nomeItem}' em {$escolaRow} ({$row->data}).",
                        ];
                    }
                }
            }

            $porEscola[$escolaRow] = [
                'escola' => $escolaRow,
                'porcoes_previstas' => ($porEscola[$escolaRow]['porcoes_previstas'] ?? 0) + (int) ($fatorServico['previstas'] ?? 0),
                'porcoes_servidas' => ($porEscola[$escolaRow]['porcoes_servidas'] ?? 0) + (int) ($fatorServico['servidas'] ?? 0),
            ];
        }

        foreach ($servicos as $s) {
            $prev = (int) $s->porcoes_previstas;
            $serv = (int) $s->porcoes_servidas;
            if ($prev > 0 && abs($serv - $prev) / $prev >= 0.2) {
                $delta = $serv - $prev;
                $incongruencias[] = [
                    'nivel' => $serv > $prev ? 'erro' : 'alerta',
                    'codigo' => 'desvio_porcoes',
                    'mensagem' => sprintf(
                        '%s · %s (%s): previstas %d, servidas %d (Δ %+d, %.0f%%).',
                        $s->escola,
                        $s->refeicao,
                        $s->data,
                        $prev,
                        $serv,
                        $delta,
                        ($delta / $prev) * 100
                    ),
                ];
            }
            if (empty($s->confirmado_em)) {
                $incongruencias[] = [
                    'nivel' => 'alerta',
                    'codigo' => 'sem_confirmacao',
                    'mensagem' => "Serviço sem confirmação: {$s->escola} · {$s->refeicao} em {$s->data}.",
                ];
            }
        }

        $itens = array_values($porItem);
        usort($itens, static fn (array $a, array $b): int => $b['custo_previsto'] <=> $a['custo_previsto']);

        $unicas = [];
        foreach ($incongruencias as $inc) {
            $unicas[$inc['codigo'].'|'.$inc['mensagem']] = $inc;
        }

        // Calcular adesão (5.2.3)
        $adesao = self::calcularAdesao($inicio, $fim, $escola, $totServ, $porEscola);

        return [
            'periodo' => ['inicio' => $inicio, 'fim' => $fim],
            'totais' => [
                'previstas' => $totPrev,
                'servidas' => $totServ,
                'custo_previsto' => round($custoPrev, 2),
                'custo_servido' => round($custoServ, 2),
                'kcal_previsto' => round($kcalPrev, 1),
                'kcal_servido' => round($kcalServ, 1),
            ],
            'itens' => $itens,
            'incongruencias' => array_values($unicas),
            'por_escola' => array_values($porEscola),
            'adesao' => $adesao,
        ];
    }

    /**
     * @param  list<object>  $servicos
     * @return array{previstas:int,servidas:int}
     */
    private static function fatorServico(array $servicos, string $data, string $escola): array
    {
        $prev = 0;
        $serv = 0;
        $achou = false;
        foreach ($servicos as $s) {
            if ((string) $s->data !== $data) {
                continue;
            }
            if ((string) $s->escola !== $escola && $escola !== 'Rede') {
                continue;
            }
            $prev += (int) $s->porcoes_previstas;
            $serv += (int) $s->porcoes_servidas;
            $achou = true;
        }

        if (! $achou) {
            return ['previstas' => 1, 'servidas' => 0];
        }

        return ['previstas' => max(1, $prev), 'servidas' => $serv];
    }

    /**
     * Calcula indicador de adesão ao cardápio (5.2.3)
     * Porções servidas / matrículas ativas por escola/mês
     * 
     * @param string $inicio
     * @param string $fim
     * @param string|null $escola
     * @param int $totalServidas
     * @param array $porEscola
     * @return array
     */
    private static function calcularAdesao(string $inicio, string $fim, ?string $escola, int $totalServidas, array $porEscola): array
    {
        $matriculas = self::contarMatriculasAtivas($escola);
        $diasUteis = self::contarDiasUteis($inicio, $fim);
        
        // Adesão geral
        $matriculasTotal = array_sum(array_column($matriculas, 'total'));
        $expectativa = $matriculasTotal * $diasUteis;
        $percentualGeral = $expectativa > 0 ? round(($totalServidas / $expectativa) * 100, 1) : 0;

        // Adesão por escola
        $porEscolaAdesao = [];
        foreach ($porEscola as $esc) {
            $escolaNome = $esc['escola'];
            $matriculasEscola = $matriculas[$escolaNome]['total'] ?? 0;
            $expectativaEscola = $matriculasEscola * $diasUteis;
            $servidasEscola = $esc['porcoes_servidas'];
            $percentualEscola = $expectativaEscola > 0 ? round(($servidasEscola / $expectativaEscola) * 100, 1) : 0;

            $porEscolaAdesao[] = [
                'escola' => $escolaNome,
                'matriculas' => $matriculasEscola,
                'porcoes_servidas' => $servidasEscola,
                'expectativa' => $expectativaEscola,
                'percentual' => $percentualEscola,
            ];
        }

        return [
            'percentual' => $percentualGeral,
            'porcoes_servidas' => $totalServidas,
            'matriculas' => $matriculasTotal,
            'dias_uteis' => $diasUteis,
            'expectativa' => $expectativa,
            'por_escola' => $porEscolaAdesao,
        ];
    }

    /**
     * Conta matrículas ativas por escola
     * 
     * @param string|null $escolaFiltro
     * @return array<string, array{total:int}>
     */
    private static function contarMatriculasAtivas(?string $escolaFiltro = null): array
    {
        if (!Schema::hasTable('pmieducar.matricula') || 
            !Schema::hasTable('pmieducar.escola') || 
            !Schema::hasTable('cadastro.juridica')) {
            return [];
        }

        try {
            $q = DB::table('pmieducar.matricula')
                ->join('pmieducar.escola', 'pmieducar.escola.cod_escola', '=', 'pmieducar.matricula.ref_ref_cod_escola')
                ->join('cadastro.juridica', 'cadastro.juridica.idpes', '=', 'pmieducar.escola.ref_idpes')
                ->where('pmieducar.matricula.ativo', 1)
                ->where('pmieducar.matricula.ano', (int) date('Y'));

            if ($escolaFiltro !== null && $escolaFiltro !== '' && $escolaFiltro !== 'Rede') {
                $q->where('cadastro.juridica.fantasia', $escolaFiltro);
            }

            $rows = $q->groupBy('cadastro.juridica.fantasia')
                ->selectRaw('cadastro.juridica.fantasia as nome, count(*) as total')
                ->get();

            $resultado = [];
            foreach ($rows as $r) {
                $resultado[(string) $r->nome] = ['total' => (int) $r->total];
            }

            return $resultado;
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * Conta dias úteis entre duas datas (simplificado - exclui fins de semana)
     * 
     * @param string $inicio
     * @param string $fim
     * @return int
     */
    private static function contarDiasUteis(string $inicio, string $fim): int
    {
        try {
            $start = new \DateTime($inicio);
            $end = new \DateTime($fim);
            $dias = 0;

            while ($start <= $end) {
                $diaSemana = (int) $start->format('N'); // 1 (segunda) a 7 (domingo)
                if ($diaSemana <= 5) { // Segunda a sexta
                    $dias++;
                }
                $start->modify('+1 day');
            }

            return $dias;
        } catch (\Throwable) {
            return 1;
        }
    }
}
