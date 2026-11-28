<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

/**
 * Monta bandejas padrão a partir do catálogo quando o usuário não montou manualmente.
 */
final class BandejasAutomaticas
{
    /** @var array<string, list<string>> */
    private const MODELO = [
        'cafe' => ['pão', 'leite', 'banana'],
        'almoco' => ['arroz', 'feijão', 'salada', 'fruta', 'carne'],
        'lanche' => ['fruta', 'suco natural'],
        'lanche_2' => ['fruta', 'biscoito'],
        'lanche_3' => ['fruta'],
        'jantar' => ['arroz', 'feijão', 'salada'],
    ];

    /**
     * @return list<array{refeicao:string,itens:list<string>,porcoes:list<array{item:string,peso_g:?float,kcal:?float,previsto:bool}>}>
     */
    public static function montar(?array $catalogo = null): array
    {
        if (! (bool) ConfigAlimentacao::get('bandejas_automaticas', true)) {
            return [];
        }

        $catalogo ??= CatalogoItens::listar(true);
        $refeicoes = [];
        foreach (ConfigAlimentacao::slotsAtivos() as $refeicao) {
            $pool = CatalogoItens::filtrarPorRefeicao($catalogo, $refeicao);
            $porChave = [];
            foreach ($pool as $item) {
                if (empty($item['ativo'])) {
                    continue;
                }
                $porChave[CatalogoItens::chaveNome($item['nome'])] = $item['nome'];
            }

            $desejados = self::MODELO[$refeicao]
                ?? (str_starts_with($refeicao, 'lanche') ? self::MODELO['lanche'] : []);
            $itens = [];
            foreach ($desejados as $nome) {
                $chave = CatalogoItens::chaveNome($nome);
                if (isset($porChave[$chave])) {
                    $itens[] = $porChave[$chave];
                }
            }
            if ($itens === [] && $pool !== []) {
                $itens = array_values(array_slice(array_column($pool, 'nome'), 0, $refeicao === 'almoco' ? 3 : 2));
            }
            if ($itens === []) {
                continue;
            }
            $refeicoes[] = [
                'refeicao' => $refeicao,
                'itens' => $itens,
                'porcoes' => CatalogoItens::porcoesPrevistas($itens),
            ];
        }

        return $refeicoes;
    }

    /**
     * @param  list<array{refeicao?:string,itens?:list<string>,porcoes?:list<mixed>}>  $refeicoes
     * @return array{refeicoes:list<array{refeicao:string,itens:list<string>,porcoes:list<mixed>}>,automatico:bool}
     */
    public static function completar(array $refeicoes): array
    {
        $temItens = false;
        foreach ($refeicoes as $ref) {
            if (! empty($ref['itens'])) {
                $temItens = true;
                break;
            }
        }

        if (! $temItens) {
            return ['refeicoes' => self::montar(), 'automatico' => true];
        }

        $out = [];
        $completouPorcoes = false;
        foreach ($refeicoes as $ref) {
            $nome = mb_strtolower((string) ($ref['refeicao'] ?? 'refeicao'));
            $itens = array_values(array_filter(array_map('strval', $ref['itens'] ?? [])));
            if ($itens === []) {
                continue;
            }
            $porcoes = $ref['porcoes'] ?? [];
            if ($porcoes === []) {
                $porcoes = CatalogoItens::porcoesPrevistas($itens);
                $completouPorcoes = true;
            }
            $out[] = [
                'refeicao' => $nome,
                'itens' => $itens,
                'porcoes' => $porcoes,
            ];
        }

        if ($out === []) {
            return ['refeicoes' => self::montar(), 'automatico' => true];
        }

        return ['refeicoes' => $out, 'automatico' => $completouPorcoes];
    }

    /** @return array<string, string> */
    public static function valoresFormulario(?array $catalogo = null): array
    {
        $vals = [];
        foreach (array_keys(ConfigAlimentacao::slotsUi()) as $k) {
            $vals[$k] = '';
        }
        foreach (self::montar($catalogo) as $ref) {
            $k = mb_strtolower((string) ($ref['refeicao'] ?? ''));
            if (isset($vals[$k])) {
                $vals[$k] = implode(', ', $ref['itens'] ?? []);
            }
        }

        return $vals;
    }
}
