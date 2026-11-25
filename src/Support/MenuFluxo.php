<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Support;

final class MenuFluxo
{
    /**
     * Grupos laterais no estilo transporte.
     *
     * @param  array<string, bool>  $permite
     * @return list<array{nome:string,itens:list<array{rotulo:string,url:string,icone:string,chave:string}>}>
     */
    public static function grupos(array $permite): array
    {
        $item = static function (string $chave, string $rotulo, string $url, string $icone) use ($permite): ?array {
            if (! ($permite[$chave] ?? true)) {
                return null;
            }

            return compact('chave', 'rotulo', 'url', 'icone');
        };

        $filtrar = static fn (array $itens): array => array_values(array_filter($itens));

        return array_values(array_filter([
            [
                'nome' => 'Operação',
                'itens' => $filtrar([
                    $item('painel', 'Painel', '/alimentacao-escolar', 'fa-dashboard'),
                    $item('cardapio', 'Cardápio do dia', '/alimentacao-escolar/cardapio', 'fa-cutlery'),
                    $item('restricoes', 'Restrições', '/alimentacao-escolar/restricoes', 'fa-exclamation-triangle'),
                    $item('mapa', 'Mapa distribuição', '/alimentacao-escolar/mapa', 'fa-map'),
                ]),
            ],
            [
                'nome' => 'PoC',
                'itens' => $filtrar([
                    $item('demo', 'Rodar demo', '/alimentacao-escolar/demo', 'fa-play'),
                ]),
            ],
        ], static fn (array $g): bool => $g['itens'] !== []));
    }
}
