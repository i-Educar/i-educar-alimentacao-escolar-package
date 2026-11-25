<?php

use App\Menu;
use iEducar\Packages\BuritiAlimentacaoEscolar\Support\Processos;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $raiz = Menu::query()->updateOrCreate(['process' => Processos::RAIZ], [
            'parent_id' => null,
            'title' => 'Alimentação escolar',
            'description' => 'Merenda / PNAE (Laguna PoC)',
            'link' => '/alimentacao-escolar',
            'icon' => 'fa-cutlery',
            'order' => 9,
            'type' => 1,
            'active' => true,
        ]);

        Menu::query()->updateOrCreate(['process' => Processos::CARDAPIO], [
            'parent_id' => $raiz->getKey(),
            'title' => 'Cardápio do dia',
            'description' => 'Consulta o cardápio e restrições',
            'link' => '/alimentacao-escolar/cardapio',
            'order' => 1,
            'type' => 3,
            'active' => true,
        ]);

        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->pluck('cod_tipo_usuario');
        foreach ($tipos as $tipoId) {
            foreach ([Processos::RAIZ, Processos::CARDAPIO] as $processo) {
                $menuId = Menu::query()->where('process', $processo)->value('id');
                if ($menuId === null) {
                    continue;
                }
                DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                    ['ref_cod_tipo_usuario' => $tipoId, 'menu_id' => $menuId],
                    ['cadastra' => 1, 'visualiza' => 1, 'exclui' => 0],
                );
            }
        }
    }

    public function down(): void
    {
        foreach ([Processos::CARDAPIO, Processos::RAIZ] as $processo) {
            $menuId = Menu::query()->where('process', $processo)->value('id');
            if ($menuId) {
                DB::table('pmieducar.menu_tipo_usuario')->where('menu_id', $menuId)->delete();
                Menu::query()->whereKey($menuId)->delete();
            }
        }
    }
};
