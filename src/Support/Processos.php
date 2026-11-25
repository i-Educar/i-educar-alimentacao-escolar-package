<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Support;

final class Processos
{
    public const RAIZ = 881100;

    public const CARDAPIO = 881110;

    /** @return array<int, string> */
    public static function titulos(): array
    {
        return [
            self::RAIZ => 'Alimentação escolar',
            self::CARDAPIO => 'Cardápio do dia',
        ];
    }
}
