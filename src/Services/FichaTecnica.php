<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha técnica de preparações (5.2.7, 5.2.8, 5.2.9)
 */
final class FichaTecnica
{
    /**
     * Lista todas as fichas técnicas
     * 
     * @param string|null $escola Filtrar por escola
     * @param bool $apenasAtivas Incluir apenas fichas ativas
     * @return list<array>
     */
    public static function listar(?string $escola = null, bool $apenasAtivas = true): array
    {
        if (!Schema::hasTable('alimentacao.ficha_tecnica')) {
            return [];
        }

        $q = DB::table('alimentacao.ficha_tecnica')->orderBy('nome');

        if ($escola !== null && $escola !== '') {
            $q->where(function ($w) use ($escola) {
                $w->where('escola', $escola)->orWhereNull('escola');
            });
        }

        if ($apenasAtivas) {
            $q->where('ativo', true);
        }

        return $q->get()->map(static fn ($r): array => [
            'id' => (int) $r->id,
            'nome' => (string) $r->nome,
            'escola' => (string) ($r->escola ?? 'Todas'),
            'categoria' => (string) $r->categoria,
            'rendimento_porcoes' => (int) ($r->rendimento_porcoes ?? 0),
            'peso_porcao_g' => (float) ($r->peso_porcao_g ?? 0),
            'kcal_porcao' => (float) ($r->kcal_porcao ?? 0),
            'custo_porcao' => (float) ($r->custo_porcao ?? 0),
            'tempo_preparo_min' => (int) ($r->tempo_preparo_min ?? 0),
            'ativo' => (bool) $r->ativo,
            'ingredientes' => json_decode((string) ($r->ingredientes_json ?? '[]'), true) ?: [],
        ])->all();
    }

    /**
     * Busca uma ficha técnica por ID
     * 
     * @param int $id
     * @return array|null
     */
    public static function buscar(int $id): ?array
    {
        if (!Schema::hasTable('alimentacao.ficha_tecnica')) {
            return null;
        }

        $r = DB::table('alimentacao.ficha_tecnica')->where('id', $id)->first();
        if ($r === null) {
            return null;
        }

        return [
            'id' => (int) $r->id,
            'nome' => (string) $r->nome,
            'escola' => (string) ($r->escola ?? ''),
            'categoria' => (string) $r->categoria,
            'modo_preparo' => (string) ($r->modo_preparo ?? ''),
            'utensilios' => (string) ($r->utensilios ?? ''),
            'tempo_preparo_min' => (int) ($r->tempo_preparo_min ?? 0),
            'rendimento_porcoes' => (int) ($r->rendimento_porcoes ?? 0),
            'peso_porcao_g' => (float) ($r->peso_porcao_g ?? 0),
            'custo_total' => (float) ($r->custo_total ?? 0),
            'custo_porcao' => (float) ($r->custo_porcao ?? 0),
            'kcal_porcao' => (float) ($r->kcal_porcao ?? 0),
            'proteina_g' => (float) ($r->proteina_g ?? 0),
            'carboidrato_g' => (float) ($r->carboidrato_g ?? 0),
            'lipidio_g' => (float) ($r->lipidio_g ?? 0),
            'fibra_g' => (float) ($r->fibra_g ?? 0),
            'sodio_mg' => (float) ($r->sodio_mg ?? 0),
            'ativo' => (bool) $r->ativo,
            'ingredientes' => json_decode((string) ($r->ingredientes_json ?? '[]'), true) ?: [],
            'created_at' => (string) ($r->created_at ?? ''),
            'updated_at' => (string) ($r->updated_at ?? ''),
        ];
    }

    /**
     * Salva ou atualiza uma ficha técnica
     * 
     * @param int|null $id ID para atualizar, null para criar
     * @param array $dados Dados da ficha
     * @param int|null $usuarioId ID do usuário que está salvando
     * @return int ID da ficha criada ou atualizada
     */
    public static function salvar(?int $id, array $dados, ?int $usuarioId = null): int
    {
        if (!Schema::hasTable('alimentacao.ficha_tecnica')) {
            throw new \RuntimeException('Tabela alimentacao.ficha_tecnica ausente — rode migrate.');
        }

        $nome = trim((string) ($dados['nome'] ?? ''));
        if ($nome === '') {
            throw new \InvalidArgumentException('Nome da preparação é obrigatório.');
        }

        $ingredientes = $dados['ingredientes'] ?? [];
        if (!is_array($ingredientes)) {
            $ingredientes = [];
        }

        // Calcular totais nutricionais e custo a partir dos ingredientes
        $nutricao = self::calcularNutricao($ingredientes);
        $custoTotal = self::calcularCusto($ingredientes);
        $rendimento = max(1, (int) ($dados['rendimento_porcoes'] ?? 1));
        $custoPorcao = $rendimento > 0 ? round($custoTotal / $rendimento, 2) : 0;

        $payload = [
            'nome' => $nome,
            'escola' => trim((string) ($dados['escola'] ?? '')) ?: null,
            'categoria' => (string) ($dados['categoria'] ?? 'principal'),
            'modo_preparo' => trim((string) ($dados['modo_preparo'] ?? '')) ?: null,
            'utensilios' => trim((string) ($dados['utensilios'] ?? '')) ?: null,
            'tempo_preparo_min' => (int) ($dados['tempo_preparo_min'] ?? 0) ?: null,
            'rendimento_porcoes' => $rendimento > 0 ? $rendimento : null,
            'peso_porcao_g' => (float) ($dados['peso_porcao_g'] ?? 0) ?: null,
            'ingredientes_json' => json_encode($ingredientes, JSON_UNESCAPED_UNICODE),
            'custo_total' => $custoTotal > 0 ? $custoTotal : null,
            'custo_porcao' => $custoPorcao > 0 ? $custoPorcao : null,
            'kcal_porcao' => $rendimento > 0 ? round($nutricao['kcal'] / $rendimento, 2) : null,
            'proteina_g' => $rendimento > 0 ? round($nutricao['proteina'] / $rendimento, 2) : null,
            'carboidrato_g' => $rendimento > 0 ? round($nutricao['carboidrato'] / $rendimento, 2) : null,
            'lipidio_g' => $rendimento > 0 ? round($nutricao['lipidio'] / $rendimento, 2) : null,
            'fibra_g' => $rendimento > 0 ? round($nutricao['fibra'] / $rendimento, 2) : null,
            'sodio_mg' => $rendimento > 0 ? round($nutricao['sodio'] / $rendimento, 2) : null,
            'ativo' => (bool) ($dados['ativo'] ?? true),
            'usuario_id' => $usuarioId,
            'updated_at' => now(),
        ];

        if ($id !== null && $id > 0) {
            DB::table('alimentacao.ficha_tecnica')->where('id', $id)->update($payload);
            return $id;
        } else {
            $payload['created_at'] = now();
            return (int) DB::table('alimentacao.ficha_tecnica')->insertGetId($payload);
        }
    }

    /**
     * Copia uma ficha técnica para outra(s) escola(s)
     * 
     * @param int $fichaId ID da ficha origem
     * @param array $escolasDestino Lista de nomes de escolas destino
     * @param int|null $usuarioId
     * @return int Número de cópias criadas
     */
    public static function copiarParaEscolas(int $fichaId, array $escolasDestino, ?int $usuarioId = null): int
    {
        $origem = self::buscar($fichaId);
        if ($origem === null) {
            throw new \InvalidArgumentException('Ficha técnica não encontrada.');
        }

        $copiadas = 0;
        foreach ($escolasDestino as $escola) {
            $escola = trim((string) $escola);
            if ($escola === '' || $escola === $origem['escola']) {
                continue;
            }

            $dados = $origem;
            $dados['escola'] = $escola;
            $dados['nome'] = $origem['nome'] . ' (cópia)';
            unset($dados['id'], $dados['created_at'], $dados['updated_at']);

            self::salvar(null, $dados, $usuarioId);
            $copiadas++;
        }

        return $copiadas;
    }

    /**
     * Calcula informação nutricional total a partir dos ingredientes
     * 
     * @param array $ingredientes
     * @return array
     */
    private static function calcularNutricao(array $ingredientes): array
    {
        $totais = ['kcal' => 0, 'proteina' => 0, 'carboidrato' => 0, 'lipidio' => 0, 'fibra' => 0, 'sodio' => 0];

        foreach ($ingredientes as $ing) {
            $quantidade = (float) ($ing['quantidade'] ?? 0);
            if ($quantidade <= 0) {
                continue;
            }

            // Buscar dados nutricionais do item (porção de referência do catálogo).
            $itemId = (int) ($ing['item_id'] ?? 0);
            if ($itemId <= 0) {
                continue;
            }
            $item = CatalogoItens::encontrar($itemId);
            if ($item === null) {
                continue;
            }
            $unidade = mb_strtolower((string) ($ing['unidade'] ?? 'g'));
            $pesoRef = (float) ($item['peso_porcao_g'] ?? 0);
            // Fator: qtd em g / peso da porção de referência; kg→g; un/porção ≈ porções.
            $fator = match (true) {
                in_array($unidade, ['un', 'porção', 'porcao'], true) => $quantidade,
                $unidade === 'kg' && $pesoRef > 0 => ($quantidade * 1000) / $pesoRef,
                $unidade === 'g' && $pesoRef > 0 => $quantidade / $pesoRef,
                $unidade === 'kg' => $quantidade * 10, // fallback ~100g/porção
                $unidade === 'g' => $quantidade / 100,
                default => $quantidade,
            };
            $totais['kcal'] += (float) ($item['kcal_porcao'] ?? 0) * $fator;
            $totais['proteina'] += (float) ($item['proteina_g'] ?? 0) * $fator;
            $totais['carboidrato'] += (float) ($item['carboidrato_g'] ?? 0) * $fator;
            $totais['lipidio'] += (float) ($item['lipidio_g'] ?? 0) * $fator;
            $totais['fibra'] += (float) ($item['fibra_g'] ?? 0) * $fator;
            $totais['sodio'] += (float) ($item['sodio_mg'] ?? 0) * $fator;
        }

        return $totais;
    }

    /**
     * Calcula custo total a partir dos ingredientes
     * 
     * @param array $ingredientes
     * @return float
     */
    private static function calcularCusto(array $ingredientes): float
    {
        $total = 0;

        foreach ($ingredientes as $ing) {
            $quantidade = (float) ($ing['quantidade'] ?? 0);
            $custoUnitario = (float) ($ing['custo_unitario'] ?? 0);
            
            if ($quantidade > 0 && $custoUnitario > 0) {
                $total += $quantidade * $custoUnitario;
                continue;
            }

            $itemId = (int) ($ing['item_id'] ?? 0);
            if ($itemId > 0) {
                $item = CatalogoItens::encontrar($itemId);
                if ($item !== null && ($item['custo_unitario'] ?? null) !== null) {
                    $total += $quantidade * (float) $item['custo_unitario'];
                }
            }
        }

        return round($total, 2);
    }

    /**
     * Exclui uma ficha técnica (soft delete)
     * 
     * @param int $id
     * @return bool
     */
    public static function excluir(int $id): bool
    {
        if (!Schema::hasTable('alimentacao.ficha_tecnica')) {
            return false;
        }

        return DB::table('alimentacao.ficha_tecnica')
            ->where('id', $id)
            ->update(['ativo' => false, 'updated_at' => now()]) > 0;
    }
}
