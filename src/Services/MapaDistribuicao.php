<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

final class MapaDistribuicao
{
    /** @param list<array{escola:string,refeicao:string,quantidade:int}> $linhas */
    public static function consolidar(array $linhas): array {
        $por = []; $total=0;
        foreach ($linhas as $l) {
            $k = $l['escola'].'|'.$l['refeicao'];
            $por[$k] = ($por[$k] ?? 0) + (int)$l['quantidade'];
            $total += (int)$l['quantidade'];
        }
        return ['por_chave'=>$por,'total'=>$total];
    }
}
