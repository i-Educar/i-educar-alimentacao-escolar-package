<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

final class RestricaoAlimentar
{
    /** Sinônimos comuns para cruzar item do cardápio × restrição do aluno. */
    private const array SINONIMOS = [
        'leite' => ['leite', 'lactose', 'laticinio', 'laticínio', 'queijo', 'iogurte', 'manteiga', 'nata'],
        'lactose' => ['leite', 'lactose', 'laticinio', 'laticínio', 'queijo', 'iogurte'],
        'gluten' => ['gluten', 'glúten', 'trigo', 'pao', 'pão', 'macarrao', 'macarrão', 'bolo', 'biscoito'],
        'glúten' => ['gluten', 'glúten', 'trigo', 'pao', 'pão', 'macarrao', 'macarrão'],
        'amendoim' => ['amendoim', 'amendoim', 'castanha', 'nozes', 'oleaginosa'],
        'ovo' => ['ovo', 'ovos', 'clara', 'gema'],
        'peixe' => ['peixe', 'pescado', 'camarao', 'camarão', 'frutos do mar', 'marisco'],
        'porco' => ['porco', 'suino', 'suíno', 'presunto', 'bacon', 'linguiça', 'linguica'],
        'carne' => ['carne', 'bovina', 'frango', 'ave', 'porco'],
    ];

    /** @param list<string> $restricoes */
    public function __construct(
        private readonly int $alunoId,
        private readonly array $restricoes,
    ) {}

    public function alunoId(): int
    {
        return $this->alunoId;
    }

    /** @return list<string> */
    public function restricoes(): array
    {
        return $this->restricoes;
    }

    /**
     * @param  list<string>  $itens
     */
    public function conflita(array $itens): bool
    {
        return $this->itensEmConflito($itens) !== [];
    }

    /**
     * @param  list<string>  $itens
     * @return list<array{item:string,restricao:string}>
     */
    public function itensEmConflito(array $itens): array
    {
        $out = [];
        foreach ($itens as $item) {
            $itemNorm = $this->norm((string) $item);
            foreach ($this->restricoes as $restricao) {
                $rNorm = $this->norm((string) $restricao);
                if ($itemNorm === '' || $rNorm === '') {
                    continue;
                }
                if ($this->bate($itemNorm, $rNorm)) {
                    $out[] = ['item' => (string) $item, 'restricao' => (string) $restricao];
                }
            }
        }

        return $out;
    }

    private function bate(string $item, string $restricao): bool
    {
        if ($item === $restricao || str_contains($item, $restricao) || str_contains($restricao, $item)) {
            return true;
        }
        foreach (self::SINONIMOS as $chave => $lista) {
            $chaveN = $this->norm($chave);
            $listaN = array_map(fn ($s) => $this->norm((string) $s), $lista);
            $restricaoNoGrupo = $restricao === $chaveN || in_array($restricao, $listaN, true) || str_contains($restricao, $chaveN);
            $itemNoGrupo = $item === $chaveN || in_array($item, $listaN, true) || str_contains($item, $chaveN);
            if ($restricaoNoGrupo && $itemNoGrupo) {
                return true;
            }
        }

        return false;
    }

    private function norm(string $s): string
    {
        $s = mb_strtolower(trim($s));
        $s = str_replace(
            ['á', 'à', 'ã', 'â', 'é', 'ê', 'í', 'ó', 'ô', 'õ', 'ú', 'ç'],
            ['a', 'a', 'a', 'a', 'e', 'e', 'i', 'o', 'o', 'o', 'u', 'c'],
            $s
        );

        return preg_replace('/\s+/', ' ', $s) ?? $s;
    }
}
