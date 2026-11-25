<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Support;

/**
 * Cor-base e ícones do módulo (UI-MODULOS-LAGUNA.md).
 */
final class Identidade
{
    public const COR = '#0f6b4c';

    public const DESTAQUE = '#2d8a5e';

    public const COR_CLARA = '#e5f6ec';

    public const ICONE = 'fa-cutlery';

    public const NOME = 'Alimentação';

    public const DESCRICAO = 'Cardápio, restrições e distribuição da merenda';

    /** @return array{cor:string,destaque:string,clara:string,icone:string,nome:string,descricao:string} */
    public static function tokens(): array
    {
        return [
            'cor' => self::COR,
            'destaque' => self::DESTAQUE,
            'clara' => self::COR_CLARA,
            'icone' => self::ICONE,
            'nome' => self::NOME,
            'descricao' => self::DESCRICAO,
        ];
    }
}
