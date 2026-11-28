<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

final class MapaDistribuicao
{
    /**
     * @param  list<array{escola:string,refeicao:string,quantidade:int,data?:string}>  $linhas
     * @return array{
     *   por_chave: array<string,int>,
     *   total: int,
     *   rede: array{escolas:int,linhas:int,total:int,por_refeicao:array<string,int>},
     *   escolas: list<array{
     *     nome:string,total:int,pct_rede:float,itens:array<string,int>,
     *     max_item:int,datas:list<string>,eh_rede:bool
     *   }>
     * }
     */
    public static function consolidar(array $linhas): array
    {
        $por = [];
        $total = 0;
        $porEscola = [];
        $porRefeicaoRede = [];
        $datasPorEscola = [];

        foreach ($linhas as $l) {
            $escola = trim((string) ($l['escola'] ?? 'Escola'));
            $refeicao = mb_strtolower(trim((string) ($l['refeicao'] ?? 'refeicao')));
            $qtd = (int) ($l['quantidade'] ?? 0);
            if ($escola === '' || $qtd <= 0) {
                continue;
            }

            $k = $escola.'|'.$refeicao;
            $por[$k] = ($por[$k] ?? 0) + $qtd;
            $total += $qtd;

            $porEscola[$escola]['itens'][$refeicao] = ($porEscola[$escola]['itens'][$refeicao] ?? 0) + $qtd;
            $porEscola[$escola]['total'] = ($porEscola[$escola]['total'] ?? 0) + $qtd;

            $porRefeicaoRede[$refeicao] = ($porRefeicaoRede[$refeicao] ?? 0) + $qtd;

            if (! empty($l['data'])) {
                $datasPorEscola[$escola][(string) $l['data']] = true;
            }
        }

        $escolas = [];
        foreach ($porEscola as $nome => $dados) {
            $itens = $dados['itens'] ?? [];
            ksort($itens);
            $tot = (int) ($dados['total'] ?? 0);
            $datas = array_keys($datasPorEscola[$nome] ?? []);
            rsort($datas);
            $escolas[] = [
                'nome' => $nome,
                'total' => $tot,
                'pct_rede' => $total > 0 ? round(($tot / $total) * 100, 1) : 0.0,
                'itens' => $itens,
                'max_item' => max(1, ...array_values($itens ?: [1])),
                'datas' => $datas,
                'eh_rede' => self::ehAgregadoRede($nome),
            ];
        }

        usort($escolas, static function (array $a, array $b): int {
            // Rede agregada primeiro na hierarquia geral; escolas por volume.
            if ($a['eh_rede'] !== $b['eh_rede']) {
                return $a['eh_rede'] ? -1 : 1;
            }

            return $b['total'] <=> $a['total'];
        });

        $escolasUnidades = array_values(array_filter($escolas, static fn (array $e): bool => ! $e['eh_rede']));
        arsort($porRefeicaoRede);

        return [
            'por_chave' => $por,
            'total' => $total,
            'rede' => [
                'escolas' => count($escolasUnidades),
                'linhas' => count($por),
                'total' => $total,
                'por_refeicao' => $porRefeicaoRede,
            ],
            'escolas' => $escolas,
            'unidades' => $escolasUnidades,
        ];
    }

    public static function ehAgregadoRede(string $nome): bool
    {
        $n = mb_strtolower(trim($nome));

        return in_array($n, ['rede', 'rede municipal', 'sme', 'secretaria'], true);
    }

    public static function rotuloRefeicao(string $ref): string
    {
        $map = [
            'cafe' => 'Café da manhã',
            'café' => 'Café da manhã',
            'almoco' => 'Almoço',
            'almoço' => 'Almoço',
            'lanche' => 'Lanche',
            'jantar' => 'Jantar',
        ];

        return $map[mb_strtolower($ref)] ?? ucfirst($ref);
    }
}
