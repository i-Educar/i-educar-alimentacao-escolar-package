<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class CatalogoItens
{
    /** @return list<array{id:int,nome:string,categoria:string,grupo_pnae:string,origem_af:bool,ativo:bool,unidade:string}> */
    public static function listar(bool $somenteAtivos = true): array
    {
        if (! Schema::hasTable('alimentacao.item')) {
            return [];
        }

        $q = DB::table('alimentacao.item')->orderBy('nome');
        if ($somenteAtivos) {
            $q->where('ativo', true);
        }

        return $q->get()->map(static fn ($r): array => [
            'id' => (int) $r->id,
            'nome' => (string) $r->nome,
            'categoria' => (string) $r->categoria,
            'grupo_pnae' => (string) $r->grupo_pnae,
            'origem_af' => (bool) $r->origem_af,
            'ativo' => (bool) $r->ativo,
            'unidade' => (string) ($r->unidade ?? 'porção'),
        ])->all();
    }

    /**
     * @param  array{nome:string,categoria?:string,grupo_pnae?:string,origem_af?:bool,ativo?:bool,unidade?:string}  $dados
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

        if ($id !== null && $id > 0) {
            DB::table('alimentacao.item')->where('id', $id)->update($payload);

            return $id;
        }

        $payload['created_at'] = now();
        $payload['alergenos'] = json_encode([]);

        return (int) DB::table('alimentacao.item')->insertGetId($payload);
    }

    /** @return array<string, array{categoria:string,grupo_pnae:string,origem_af:bool}> */
    public static function mapaPorNome(): array
    {
        $map = [];
        foreach (self::listar(false) as $item) {
            $map[mb_strtolower($item['nome'])] = [
                'categoria' => $item['categoria'],
                'grupo_pnae' => $item['grupo_pnae'],
                'origem_af' => $item['origem_af'],
            ];
        }

        return $map;
    }
}
