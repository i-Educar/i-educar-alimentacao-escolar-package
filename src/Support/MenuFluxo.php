<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Support;

final class MenuFluxo
{
    /**
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
                'nome' => 'Dia a dia',
                'itens' => $filtrar([
                    $item('painel', 'Início', '/alimentacao-escolar', 'fa-th-large'),
                    $item('cardapio', 'Cardápio do dia', '/alimentacao-escolar/cardapio', 'fa-cutlery'),
                    $item('cardapio_lote', 'Cardápio da semana', '/alimentacao-escolar/cardapio-lote', 'fa-calendar'),
                    $item('itens', 'Alimentos (PNAE)', '/alimentacao-escolar/itens', 'fa-leaf'),
                    $item('fichas_tecnicas', 'Fichas técnicas', '/alimentacao-escolar/fichas-tecnicas', 'fa-file-text-o'),
                    $item('perfil', 'Perfil / porções', '/alimentacao-escolar/perfil', 'fa-building'),
                    $item('restricoes', 'Restrições alimentares', '/alimentacao-escolar/restricoes', 'fa-medkit'),
                ]),
            ],
            [
                'nome' => 'Consultas e decisão',
                'itens' => $filtrar([
                    $item('mapa', 'Mapa de distribuição', '/alimentacao-escolar/mapa', 'fa-map-o'),
                    $item('balanco', 'Balanço e compras', '/alimentacao-escolar/balanco', 'fa-balance-scale'),
                    $item('sobras', 'Sobras e desperdício', '/alimentacao-escolar/sobras', 'fa-trash'),
                    $item('cronograma', 'Cronograma PDF', '/alimentacao-escolar/cardapio/cronograma-pdf', 'fa-print'),
                    $item('historico', 'Histórico / auditoria', '/alimentacao-escolar/historico', 'fa-history'),
                ]),
            ],
            [
                'nome' => 'Gestão',
                'itens' => $filtrar([
                    $item('config', 'Configurações', '/configuracoes/modulos?modulo=alimentacao', 'fa-sliders'),
                    $item('demo', 'Ferramentas de apoio', '/alimentacao-escolar/demo', 'fa-wrench'),
                ]),
            ],
        ], static fn (array $g): bool => $g['itens'] !== []));
    }
}
