<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

/**
 * Regras mínimas PNAE (Res. FNDE nº 6/2020, leitura operacional para PoC).
 * Nota: PNATE é transporte escolar; merenda usa PNAE.
 */
final class PnaeRegras
{
    /**
     * @param  list<array{refeicao:string,itens:list<string>}>  $refeicoes
     * @return list<array{nivel:string,codigo:string,mensagem:string}>
     */
    public static function avaliar(array $refeicoes): array
    {
        $itens = [];
        foreach ($refeicoes as $ref) {
            foreach ($ref['itens'] ?? [] as $item) {
                $itens[] = mb_strtolower(trim((string) $item));
            }
        }
        $itens = array_values(array_filter($itens, static fn (string $s): bool => $s !== ''));

        $alertas = [];
        if ($itens === []) {
            $alertas[] = self::a('erro', 'pnae.vazio', 'Informe ao menos um item nas refeições do dia.');

            return $alertas;
        }

        $catalogo = CatalogoItens::mapaPorNome();
        $grupos = [];
        $categorias = [];
        $af = 0;
        $totalCat = 0;
        $ultraprocessados = 0;

        foreach ($itens as $nome) {
            $meta = $catalogo[$nome] ?? null;
            if ($meta === null) {
                // Heurística leve para itens ainda não cadastrados
                if (preg_match('/fruta|banana|maca|maçã|laranja|mamao|mamão|abacaxi|salada|alface|tomate|cenoura|legume|hortali/', $nome)) {
                    $meta = ['categoria' => 'in_natura', 'grupo_pnae' => str_contains($nome, 'fruta') || preg_match('/banana|maca|maçã|laranja/', $nome) ? 'fruta' : 'hortalica', 'origem_af' => true];
                } elseif (preg_match('/refrigerante|suco de caixinha|biscoito recheado|salgadinho|embutido/', $nome)) {
                    $meta = ['categoria' => 'ultraprocessado', 'grupo_pnae' => 'outro', 'origem_af' => false];
                } else {
                    $meta = ['categoria' => 'minimamente', 'grupo_pnae' => 'outro', 'origem_af' => false];
                }
            }

            $grupos[$meta['grupo_pnae']] = true;
            $categorias[$meta['categoria']] = ($categorias[$meta['categoria']] ?? 0) + 1;
            $totalCat++;
            if ($meta['origem_af']) {
                $af++;
            }
            if ($meta['categoria'] === 'ultraprocessado') {
                $ultraprocessados++;
            }
            if (str_contains($nome, 'refrigerante') || str_contains($nome, 'refri')) {
                $alertas[] = self::a('erro', 'pnae.refrigerante', "Item proibido/desaconselhado pelo PNAE: «{$nome}».");
            }
        }

        if (! isset($grupos['fruta']) && ! isset($grupos['hortalica'])) {
            $alertas[] = self::a('erro', 'pnae.hortifruti', 'PNAE: o cardápio do dia deve incluir fruta ou hortaliça.');
        } elseif (! isset($grupos['fruta'])) {
            $alertas[] = self::a('alerta', 'pnae.fruta', 'PNAE: recomenda-se oferecer fruta in natura no dia.');
        }

        if (! isset($grupos['cereal']) && ! isset($grupos['leguminosa'])) {
            $alertas[] = self::a('alerta', 'pnae.base', 'PNAE: inclua cereal e/ou leguminosa na refeição principal.');
        }

        $pctAf = $totalCat > 0 ? (int) round(100 * $af / $totalCat) : 0;
        $metaAf = (int) config('alimentacao.pnae.meta_af_percentual', 30);
        if ($pctAf < $metaAf) {
            $alertas[] = self::a(
                'alerta',
                'pnae.af',
                "PNAE: agricultura familiar em {$pctAf}% dos itens (meta indicativa {$metaAf}%). Cadastre itens com origem AF."
            );
        }

        if ($ultraprocessados > 0) {
            $alertas[] = self::a(
                'alerta',
                'pnae.ultra',
                "PNAE: {$ultraprocessados} item(ns) ultraprocessado(s) — restringir frequência e priorizar in natura."
            );
        }

        $refeicoesNomes = array_map(static fn ($r) => mb_strtolower((string) ($r['refeicao'] ?? '')), $refeicoes);
        if (! in_array('almoco', $refeicoesNomes, true) && ! in_array('almoço', $refeicoesNomes, true)) {
            $alertas[] = self::a('alerta', 'pnae.almoco', 'PNAE: recomenda-se registrar a refeição principal (almoço) do dia.');
        }

        if ($alertas === []) {
            $alertas[] = self::a('ok', 'pnae.ok', "Cardápio compatível com as regras PNAE operacionais (AF {$pctAf}%).");
        }

        return $alertas;
    }

    /** @return array{nivel:string,codigo:string,mensagem:string} */
    private static function a(string $nivel, string $codigo, string $mensagem): array
    {
        return compact('nivel', 'codigo', 'mensagem');
    }
}
