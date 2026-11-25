<?php

use App\Menu;
use App\Services\MenuCacheService;
use iEducar\Packages\BuritiAlimentacaoEscolar\Support\Processos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menus SME no padrão transporte: raiz + grupos + folhas + ACL + flush cache.
 */
return new class extends Migration
{
    public function up(): void
    {
        $raiz = Menu::query()->updateOrCreate(['process' => Processos::RAIZ], [
            'parent_id' => null,
            'title' => 'Alimentação',
            'description' => 'Merenda / PNAE (Laguna)',
            'link' => '/alimentacao-escolar',
            'icon' => 'fa-cutlery',
            'order' => 9,
            'type' => 1,
            'active' => true,
        ]);

        $operacao = Menu::query()->updateOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'Operação'],
            ['order' => 1, 'type' => 2, 'process' => null, 'active' => true, 'link' => null],
        );
        $poc = Menu::query()->updateOrCreate(
            ['parent_id' => $raiz->getKey(), 'title' => 'PoC'],
            ['order' => 2, 'type' => 2, 'process' => null, 'active' => true, 'link' => null],
        );

        $links = [
            [Processos::PAINEL, $operacao->getKey(), 'Painel', '/alimentacao-escolar', 'Visão geral da merenda'],
            [Processos::CARDAPIO, $operacao->getKey(), 'Cardápio do dia', '/alimentacao-escolar/cardapio', 'Cardápio e conflitos'],
            [Processos::RESTRICOES, $operacao->getKey(), 'Restrições alimentares', '/alimentacao-escolar/restricoes', 'Restrições por aluno'],
            [Processos::MAPA, $operacao->getKey(), 'Mapa de distribuição', '/alimentacao-escolar/mapa', 'Porções por escola'],
            [Processos::DEMO, $poc->getKey(), 'Demo PoC', '/alimentacao-escolar/demo', 'Seed de demonstração'],
        ];

        foreach ($links as [$processo, $pai, $titulo, $link, $descricao]) {
            Menu::query()->updateOrCreate(['process' => $processo], [
                'parent_id' => $pai,
                'title' => $titulo,
                'description' => $descricao,
                'link' => $link,
                'order' => 0,
                'type' => 3,
                'active' => true,
                'icon' => null,
            ]);
        }

        $processos = [Processos::RAIZ, Processos::PAINEL, Processos::CARDAPIO, Processos::RESTRICOES, Processos::MAPA, Processos::DEMO];
        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario']);

        foreach ($tipos as $tipo) {
            foreach ($processos as $processo) {
                $menuId = Menu::query()->where('process', $processo)->value('id');
                if ($menuId === null) {
                    continue;
                }
                DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                    ['ref_cod_tipo_usuario' => $tipo->cod_tipo_usuario, 'menu_id' => $menuId],
                    ['cadastra' => 1, 'visualiza' => 1, 'exclui' => 0],
                );
            }
        }

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }

    public function down(): void
    {
        $raiz = Menu::query()->where('process', Processos::RAIZ)->first();
        if ($raiz === null) {
            return;
        }

        $grupos = Menu::query()->where('parent_id', $raiz->getKey())->pluck('id');
        $folhas = Menu::query()->whereIn('parent_id', $grupos)->pluck('id');
        $ids = $grupos->merge($folhas)->push($raiz->getKey());

        DB::table('pmieducar.menu_tipo_usuario')->whereIn('menu_id', $ids)->delete();
        Menu::query()->whereIn('id', $folhas)->delete();
        Menu::query()->whereIn('id', $grupos)->delete();
        $raiz->delete();

        if (class_exists(MenuCacheService::class)) {
            app(MenuCacheService::class)->flushAll();
        }
    }
};
