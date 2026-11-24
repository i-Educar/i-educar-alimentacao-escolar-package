<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

final class RestricaoAlimentar
{
    /** @param list<string> $restricoes */
    public function __construct(private readonly int $alunoId, private readonly array $restricoes) {}
    /** @param list<string> $itens */
    public function conflita(array $itens): bool {
        $r = array_map('mb_strtolower', $this->restricoes);
        foreach ($itens as $i) { if (in_array(mb_strtolower($i), $r, true)) return true; }
        return false;
    }
}
