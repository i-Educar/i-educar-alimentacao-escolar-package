<?php

use App\Menu;
use App\Services\MenuCacheService;
use iEducar\Packages\BuritiAlimentacaoEscolar\Support\PermissaoMatriz;
use iEducar\Packages\BuritiAlimentacaoEscolar\Support\Processos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Menus faltantes (itens/balanço/perfil/lote), dedupe de catálogo e nutrição/custo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->garantirMenus();
        $this->deduplicarItens();
        $this->preencherNutricao();
    }

    public function down(): void
    {
        // Mantém dados nutricionais; não remove menus (outros fluxos dependem).
    }

    private function garantirMenus(): void
    {
        $raiz = Menu::query()->where('process', Processos::RAIZ)->first();
        if ($raiz === null) {
            return;
        }

        $operacao = Menu::query()
            ->where('parent_id', $raiz->getKey())
            ->where('title', 'Operação')
            ->first();
        if ($operacao === null) {
            $operacao = Menu::query()->updateOrCreate(
                ['parent_id' => $raiz->getKey(), 'title' => 'Operação'],
                ['order' => 1, 'type' => 2, 'process' => null, 'active' => true, 'link' => null],
            );
        }

        $links = [
            [Processos::ITENS, 'Itens / catálogo PNAE', '/alimentacao-escolar/itens', 'Alimentos, peso, kcal e custo'],
            [Processos::CARDAPIO_LOTE, 'Cardápio em lote', '/alimentacao-escolar/cardapio-lote', 'Semana / várias escolas'],
            [Processos::PERFIL, 'Perfil escolar / porções', '/alimentacao-escolar/perfil', 'Porções previstas e servidas'],
            [Processos::BALANCO, 'Balanço e compras', '/alimentacao-escolar/balanco', 'Consumo, custo e incongruências'],
        ];

        foreach ($links as [$processo, $titulo, $link, $descricao]) {
            Menu::query()->updateOrCreate(['process' => $processo], [
                'parent_id' => $operacao->getKey(),
                'title' => $titulo,
                'description' => $descricao,
                'link' => $link,
                'order' => 0,
                'type' => 3,
                'active' => true,
                'icon' => null,
            ]);
        }

        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario', 'nivel']);
        foreach ($tipos as $tipo) {
            $matriz = PermissaoMatriz::paraNivel((int) ($tipo->nivel ?? 0));
            foreach ([Processos::ITENS, Processos::CARDAPIO_LOTE, Processos::PERFIL, Processos::BALANCO] as $processo) {
                $menuId = Menu::query()->where('process', $processo)->value('id');
                if ($menuId === null) {
                    continue;
                }
                $perm = $matriz[$processo] ?? ['visualiza' => false, 'cadastra' => false, 'exclui' => false];
                DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                    ['ref_cod_tipo_usuario' => $tipo->cod_tipo_usuario, 'menu_id' => $menuId],
                    [
                        'cadastra' => $perm['cadastra'] ? 1 : 0,
                        'visualiza' => $perm['visualiza'] ? 1 : 0,
                        'exclui' => $perm['exclui'] ? 1 : 0,
                    ],
                );
            }
        }

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    private function deduplicarItens(): void
    {
        if (! Schema::hasTable('alimentacao.item')) {
            return;
        }

        // Pares (canônico → alias a desativar). Preferimos grafia com acento.
        $pares = [
            'feijão' => ['feijao'],
            'pão' => ['pao'],
            'maçã' => ['maca', 'maça'],
        ];

        foreach ($pares as $canonico => $aliases) {
            $keeper = DB::table('alimentacao.item')->where('nome', $canonico)->first();
            foreach ($aliases as $alias) {
                $dup = DB::table('alimentacao.item')->where('nome', $alias)->first();
                if ($dup === null) {
                    continue;
                }
                if ($keeper === null) {
                    DB::table('alimentacao.item')->where('id', $dup->id)->update([
                        'nome' => $canonico,
                        'updated_at' => now(),
                    ]);
                    $keeper = DB::table('alimentacao.item')->where('id', $dup->id)->first();
                    continue;
                }
                // Transfere nutrição se o canônico estiver vazio
                $upd = ['ativo' => false, 'updated_at' => now()];
                foreach (['peso_porcao_g', 'kcal_porcao', 'custo_unitario', 'proteina_g', 'carboidrato_g', 'lipidio_g', 'fibra_g', 'sodio_mg'] as $c) {
                    if (! Schema::hasColumn('alimentacao.item', $c)) {
                        continue;
                    }
                    if (($keeper->{$c} ?? null) === null && ($dup->{$c} ?? null) !== null) {
                        $upd[$c] = $dup->{$c};
                    }
                }
                // Aplica nutrição transferida no keeper e desativa alias
                $keeperUpd = $upd;
                unset($keeperUpd['ativo']);
                if (count($keeperUpd) > 1) {
                    DB::table('alimentacao.item')->where('id', $keeper->id)->update($keeperUpd);
                }
                DB::table('alimentacao.item')->where('id', $dup->id)->update([
                    'ativo' => false,
                    'updated_at' => now(),
                ]);
            }
        }
    }

    private function preencherNutricao(): void
    {
        if (! Schema::hasTable('alimentacao.item') || ! Schema::hasColumn('alimentacao.item', 'peso_porcao_g')) {
            return;
        }

        // Valores de referência por porção escolar típica (peso g, kcal, custo R$, macros).
        $ref = [
            'arroz' => [150, 195, 0.45, 4.0, 40.0, 0.5, 0.6, 2],
            'feijão' => [100, 130, 0.55, 8.5, 22.0, 0.5, 7.0, 5],
            'feijao' => [100, 130, 0.55, 8.5, 22.0, 0.5, 7.0, 5],
            'salada' => [80, 25, 0.40, 1.0, 4.0, 0.2, 1.5, 15],
            'fruta' => [100, 60, 0.70, 0.5, 14.0, 0.2, 2.0, 1],
            'banana' => [90, 80, 0.50, 1.0, 20.0, 0.3, 2.5, 1],
            'maçã' => [130, 70, 0.80, 0.3, 18.0, 0.2, 2.2, 1],
            'maca' => [130, 70, 0.80, 0.3, 18.0, 0.2, 2.2, 1],
            'leite' => [200, 120, 0.90, 6.5, 9.5, 6.5, 0, 80],
            'pão' => [50, 135, 0.35, 4.5, 25.0, 1.5, 1.2, 220],
            'pao' => [50, 135, 0.35, 4.5, 25.0, 1.5, 1.2, 220],
            'ovos' => [50, 75, 0.60, 6.5, 0.5, 5.0, 0, 70],
            'carne' => [80, 150, 2.50, 20.0, 0, 7.0, 0, 55],
            'suco natural' => [200, 90, 0.85, 0.5, 21.0, 0.2, 0.5, 5],
            'biscoito' => [30, 140, 0.40, 2.0, 20.0, 6.0, 0.5, 120],
            'refrigerante' => [200, 85, 0.70, 0, 21.0, 0, 0, 15],
        ];

        $itens = DB::table('alimentacao.item')->get();
        foreach ($itens as $item) {
            $chave = mb_strtolower((string) $item->nome);
            if (! isset($ref[$chave])) {
                continue;
            }
            // Só preenche campos ainda vazios — não sobrescreve edição manual.
            [$peso, $kcal, $custo, $prot, $carb, $lip, $fibra, $sodio] = $ref[$chave];
            $upd = ['updated_at' => now()];
            $map = [
                'peso_porcao_g' => $peso,
                'kcal_porcao' => $kcal,
                'custo_unitario' => $custo,
                'proteina_g' => $prot,
                'carboidrato_g' => $carb,
                'lipidio_g' => $lip,
                'fibra_g' => $fibra,
                'sodio_mg' => $sodio,
            ];
            $mudou = false;
            foreach ($map as $col => $val) {
                if (($item->{$col} ?? null) === null) {
                    $upd[$col] = $val;
                    $mudou = true;
                }
            }
            if ($mudou) {
                DB::table('alimentacao.item')->where('id', $item->id)->update($upd);
            }
        }
    }
};
