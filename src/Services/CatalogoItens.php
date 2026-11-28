<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CatalogoItens
{
    /**
     * @return list<array{
     *   id:int,nome:string,categoria:string,grupo_pnae:string,origem_af:bool,ativo:bool,unidade:string,
     *   peso_porcao_g:?float,kcal_porcao:?float,proteina_g:?float,carboidrato_g:?float,lipidio_g:?float,
     *   fibra_g:?float,sodio_mg:?float,custo_unitario:?float,padrao_pnae:bool,completo:bool
     * }>
     */
    public static function listar(bool $somenteAtivos = true): array
    {
        if (! Schema::hasTable('alimentacao.item')) {
            return [];
        }

        $q = DB::table('alimentacao.item')->orderBy('nome');
        if ($somenteAtivos) {
            $q->where('ativo', true);
        }

        return $q->get()->map(static fn ($r): array => self::mapear($r))->all();
    }

    public static function encontrar(int $id): ?array
    {
        if (! Schema::hasTable('alimentacao.item') || $id <= 0) {
            return null;
        }
        $r = DB::table('alimentacao.item')->where('id', $id)->first();

        return $r ? self::mapear($r) : null;
    }

    /** Normaliza para comparação (minúsculas, sem acento). */
    public static function chaveNome(string $nome): string
    {
        $n = mb_strtolower(trim($nome));
        if (class_exists(\Normalizer::class)) {
            $n = \Normalizer::normalize($n, \Normalizer::FORM_D) ?: $n;
            $n = preg_replace('/\p{M}/u', '', $n) ?? $n;
        } else {
            $n = strtr($n, [
                'á'=>'a','à'=>'a','ã'=>'a','â'=>'a','ä'=>'a',
                'é'=>'e','ê'=>'e','è'=>'e','ë'=>'e',
                'í'=>'i','ì'=>'i','î'=>'i','ï'=>'i',
                'ó'=>'o','ò'=>'o','õ'=>'o','ô'=>'o','ö'=>'o',
                'ú'=>'u','ù'=>'u','û'=>'u','ü'=>'u',
                'ç'=>'c','ñ'=>'n',
            ]);
        }

        return preg_replace('/\s+/', ' ', $n) ?? $n;
    }

    /**
     * @param  array<string, mixed>  $dados
     */
    public static function salvar(?int $id, array $dados): int
    {
        if (! Schema::hasTable('alimentacao.item')) {
            throw new \RuntimeException('Tabela alimentacao.item ausente.');
        }

        $payload = [
            'nome' => trim((string) ($dados['nome'] ?? '')),
            'categoria' => (string) ($dados['categoria'] ?? 'in_natura'),
            'grupo_pnae' => (string) ($dados['grupo_pnae'] ?? 'outro'),
            'origem_af' => (bool) ($dados['origem_af'] ?? false),
            'ativo' => (bool) ($dados['ativo'] ?? true),
            'unidade' => (string) ($dados['unidade'] ?? 'porção'),
            'updated_at' => now(),
        ];
        if ($payload['nome'] === '') {
            throw new \InvalidArgumentException('Nome do item é obrigatório.');
        }

        foreach ([
            'peso_porcao_g', 'kcal_porcao', 'proteina_g', 'carboidrato_g',
            'lipidio_g', 'fibra_g', 'sodio_mg', 'custo_unitario',
        ] as $campo) {
            if (Schema::hasColumn('alimentacao.item', $campo)) {
                $raw = $dados[$campo] ?? null;
                $payload[$campo] = ($raw === null || $raw === '') ? null : (float) str_replace(',', '.', (string) $raw);
            }
        }
        if (Schema::hasColumn('alimentacao.item', 'padrao_pnae')) {
            $payload['padrao_pnae'] = (bool) ($dados['padrao_pnae'] ?? true);
        }
        if (Schema::hasColumn('alimentacao.item', 'refeicoes_json')) {
            $refs = $dados['refeicoes'] ?? $dados['refeicoes_json'] ?? ['cafe', 'almoco', 'lanche'];
            if (is_string($refs)) {
                $refs = array_filter(array_map('trim', explode(',', $refs)));
            }
            if (! is_array($refs) || $refs === []) {
                $refs = ['cafe', 'almoco', 'lanche'];
            }
            $refs = array_values(array_unique(array_map(
                static fn ($r): string => mb_strtolower(trim((string) $r)),
                $refs
            )));
            // Lanche cobre lanche_2 / lanche_3 na filtragem do cardápio
            $payload['refeicoes_json'] = json_encode($refs, JSON_UNESCAPED_UNICODE);
        }

        self::assertNomeUnico($payload['nome'], $id);

        if ($id !== null && $id > 0) {
            $existe = DB::table('alimentacao.item')->where('id', $id)->exists();
            if (! $existe) {
                throw new \InvalidArgumentException('Item não encontrado para edição.');
            }
            DB::table('alimentacao.item')->where('id', $id)->update($payload);

            return $id;
        }

        $payload['created_at'] = now();
        $payload['alergenos'] = json_encode([]);

        return (int) DB::table('alimentacao.item')->insertGetId($payload);
    }

    /**
     * Monta porções previstas a partir dos nomes + overrides do request.
     *
     * @param  list<string>  $itens
     * @param  array<string, array{peso_g?:float|int|string,kcal?:float|int|string}>  $overrides
     * @return list<array{item:string,peso_g:?float,kcal:?float,previsto:bool}>
     */
    public static function porcoesPrevistas(array $itens, array $overrides = []): array
    {
        $mapa = [];
        foreach (self::listar(false) as $item) {
            $mapa[self::chaveNome($item['nome'])] = $item;
            $mapa[mb_strtolower($item['nome'])] = $item;
        }

        $out = [];
        foreach ($itens as $nome) {
            $nome = trim((string) $nome);
            if ($nome === '') {
                continue;
            }
            $chave = self::chaveNome($nome);
            $meta = $mapa[$chave] ?? $mapa[mb_strtolower($nome)] ?? null;
            $ov = $overrides[$chave] ?? $overrides[mb_strtolower($nome)] ?? $overrides[$nome] ?? [];
            $out[] = [
                'item' => $nome,
                'peso_g' => isset($ov['peso_g']) && $ov['peso_g'] !== ''
                    ? (float) $ov['peso_g']
                    : ($meta['peso_porcao_g'] ?? null),
                'kcal' => isset($ov['kcal']) && $ov['kcal'] !== ''
                    ? (float) $ov['kcal']
                    : ($meta['kcal_porcao'] ?? null),
                'previsto' => true,
            ];
        }

        return $out;
    }

    /** @return array<string, array{categoria:string,grupo_pnae:string,origem_af:bool}> */
    public static function mapaPorNome(): array
    {
        $map = [];
        foreach (self::listar(false) as $item) {
            $meta = [
                'categoria' => $item['categoria'],
                'grupo_pnae' => $item['grupo_pnae'],
                'origem_af' => $item['origem_af'],
            ];
            $map[mb_strtolower($item['nome'])] = $meta;
            $map[self::chaveNome($item['nome'])] = $meta;
        }

        return $map;
    }

    private static function assertNomeUnico(string $nome, ?int $excetoId): void
    {
        $chave = self::chaveNome($nome);
        foreach (self::listar(false) as $item) {
            if ($excetoId !== null && $item['id'] === $excetoId) {
                continue;
            }
            if (self::chaveNome($item['nome']) === $chave) {
                throw new \InvalidArgumentException(
                    "Já existe o item «{$item['nome']}» (mesmo nome, ignorando acentos). Edite o existente em vez de criar outro."
                );
            }
        }
    }

    /** @param  object  $r */
    private static function mapear(object $r): array
    {
        $peso = isset($r->peso_porcao_g) ? (float) $r->peso_porcao_g : null;
        $kcal = isset($r->kcal_porcao) ? (float) $r->kcal_porcao : null;
        $custo = isset($r->custo_unitario) ? (float) $r->custo_unitario : null;

        return [
            'id' => (int) $r->id,
            'nome' => (string) $r->nome,
            'categoria' => (string) $r->categoria,
            'grupo_pnae' => (string) $r->grupo_pnae,
            'origem_af' => (bool) $r->origem_af,
            'ativo' => (bool) $r->ativo,
            'unidade' => (string) ($r->unidade ?? 'porção'),
            'peso_porcao_g' => $peso,
            'kcal_porcao' => $kcal,
            'proteina_g' => isset($r->proteina_g) ? (float) $r->proteina_g : null,
            'carboidrato_g' => isset($r->carboidrato_g) ? (float) $r->carboidrato_g : null,
            'lipidio_g' => isset($r->lipidio_g) ? (float) $r->lipidio_g : null,
            'fibra_g' => isset($r->fibra_g) ? (float) $r->fibra_g : null,
            'sodio_mg' => isset($r->sodio_mg) ? (float) $r->sodio_mg : null,
            'custo_unitario' => $custo,
            'padrao_pnae' => ! isset($r->padrao_pnae) || (bool) $r->padrao_pnae,
            'refeicoes' => self::decodeRefeicoes($r->refeicoes_json ?? null),
            'completo' => $peso !== null && $kcal !== null && $custo !== null,
        ];
    }

    /**
     * Filtra catálogo pelos slots de refeição (lanche cobre lanche_N).
     *
     * @param  list<array<string,mixed>>  $itens
     * @return list<array<string,mixed>>
     */
    public static function filtrarPorRefeicao(array $itens, string $slot): array
    {
        $slot = mb_strtolower(trim($slot));
        $base = str_starts_with($slot, 'lanche') ? 'lanche' : $slot;

        return array_values(array_filter($itens, static function (array $item) use ($base, $slot): bool {
            $refs = $item['refeicoes'] ?? [];
            if ($refs === []) {
                return true;
            }
            if (in_array($slot, $refs, true) || in_array($base, $refs, true)) {
                return true;
            }

            return false;
        }));
    }

    /** @return list<string> */
    private static function decodeRefeicoes(mixed $raw): array
    {
        if (is_array($raw)) {
            return array_values(array_map('strval', $raw));
        }
        if (! is_string($raw) || trim($raw) === '') {
            return ['cafe', 'almoco', 'lanche'];
        }
        $decoded = json_decode($raw, true);

        return is_array($decoded) && $decoded !== []
            ? array_values(array_map('strval', $decoded))
            : ['cafe', 'almoco', 'lanche'];
    }
}
