<?php

use App\Menu;
use App\Services\MenuCacheService;
use iEducar\Packages\BuritiAlimentacaoEscolar\Support\PermissaoMatriz;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $tipos = DB::table('pmieducar.tipo_usuario')->where('ativo', 1)->get(['cod_tipo_usuario', 'nivel']);

        foreach ($tipos as $tipo) {
            foreach (PermissaoMatriz::paraNivel((int) $tipo->nivel) as $processo => $flags) {
                if (! $flags['visualiza'] && ! $flags['cadastra'] && ! $flags['exclui']) {
                    continue;
                }

                $menuId = Menu::query()->where('process', $processo)->value('id');
                if ($menuId === null) {
                    continue;
                }

                DB::table('pmieducar.menu_tipo_usuario')->updateOrInsert(
                    ['ref_cod_tipo_usuario' => $tipo->cod_tipo_usuario, 'menu_id' => $menuId],
                    [
                        'visualiza' => $flags['visualiza'] ? 1 : 0,
                        'cadastra' => $flags['cadastra'] ? 1 : 0,
                        'exclui' => $flags['exclui'] ? 1 : 0,
                    ],
                );
            }
        }

        if (class_exists(MenuCacheService::class)) {
            try {
                app(MenuCacheService::class)->flushAll();
            } catch (Throwable) {
                // store sem tags: ignore
            }
        }
    }

    public function down(): void
    {
        // Mantém grants; reversão total exige reexecução do seed de menu.
    }
};
