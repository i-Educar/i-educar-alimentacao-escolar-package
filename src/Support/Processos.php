<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Support;

/**
 * Faixa 8811xx — fora do transporte (8801xx) e Portábilis.
 */
final class Processos
{
    public const RAIZ = 881100;

    public const PAINEL = 881101;

    public const CARDAPIO = 881110;

    public const CARDAPIO_LOTE = 881113;

    public const ITENS = 881114;

    public const RESTRICOES = 881111;

    public const MAPA = 881112;

    public const PERFIL = 881115;

    public const BALANCO = 881116;

    public const CONFIG = 881117;

    public const DEMO = 881190;

    /** @return array<int, string> */
    public static function titulos(): array
    {
        return [
            self::RAIZ => 'Alimentação',
            self::PAINEL => 'Painel',
            self::CARDAPIO => 'Cardápio individual',
            self::CARDAPIO_LOTE => 'Cardápio em lote',
            self::ITENS => 'Itens / catálogo PNAE',
            self::RESTRICOES => 'Restrições alimentares',
            self::MAPA => 'Mapa de distribuição',
            self::PERFIL => 'Perfil escolar / porções',
            self::BALANCO => 'Balanço e compras',
            self::CONFIG => 'Configurações do módulo',
            self::DEMO => 'Demo PoC',
        ];
    }
}
