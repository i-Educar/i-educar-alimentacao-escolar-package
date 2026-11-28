<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Support;

use App\Models\LegacyUserType;
use App\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Permissões operacionais da merenda (config + ACL do menu).
 */
final class PermissoesAlimentacao
{
    public static function usuarioAtual(): ?User
    {
        $u = Auth::user();

        return $u instanceof User ? $u : null;
    }

    /**
     * Cadastrar / editar itens do catálogo PNAE.
     * Exige montar_cardapio + (cadastra no processo ITENS ou nível admin/institucional).
     */
    public static function podeEditarItens(?User $user = null): bool
    {
        if (! (bool) config('alimentacao.montar_cardapio', true)) {
            return false;
        }

        $user ??= self::usuarioAtual();
        if ($user === null) {
            return false;
        }

        if (self::menuCadastra($user, Processos::ITENS)) {
            return true;
        }

        $nivel = self::nivelDoUsuario($user);
        // Matriz: níveis 1 e 2 cadastram catálogo; escola (4) só visualiza.
        return in_array($nivel, [LegacyUserType::LEVEL_ADMIN, LegacyUserType::LEVEL_INSTITUTIONAL], true);
    }

    /** Configurações / RT / automações — SME / admin. */
    public static function podeConfigurar(?User $user = null): bool
    {
        $user ??= self::usuarioAtual();
        if ($user === null) {
            return false;
        }
        if (self::menuCadastra($user, Processos::CONFIG)) {
            return true;
        }
        $nivel = self::nivelDoUsuario($user);

        return in_array($nivel, [LegacyUserType::LEVEL_ADMIN, LegacyUserType::LEVEL_INSTITUTIONAL], true);
    }

    /**
     * Unidade registra apenas fatos (porções servidas).
     * SME pode ajustar previstas.
     */
    public static function soRegistraFatos(?User $user = null): bool
    {
        if (! (bool) \iEducar\Packages\BuritiAlimentacaoEscolar\Services\ConfigAlimentacao::get('unidade_so_registra_fatos', true)) {
            return false;
        }
        $user ??= self::usuarioAtual();
        if ($user === null) {
            return true;
        }
        $nivel = self::nivelDoUsuario($user);
        if ($nivel === null) {
            return true;
        }

        return ! in_array($nivel, [LegacyUserType::LEVEL_ADMIN, LegacyUserType::LEVEL_INSTITUTIONAL], true);
    }

    private static function menuCadastra(User $user, int $processo): bool
    {
        if (! Schema::hasTable('pmieducar.menu_tipo_usuario')) {
            return false;
        }

        try {
            $tipoId = (int) ($user->ref_cod_tipo_usuario ?? $user->type->cod_tipo_usuario ?? 0);
            if ($tipoId <= 0) {
                return false;
            }

            $menuTable = Schema::hasTable('menus') ? 'menus' : (Schema::hasTable('portal.menu') ? 'portal.menu' : null);
            if ($menuTable === null) {
                return false;
            }

            $menuId = DB::table($menuTable)->where('process', $processo)->value('id');
            if ($menuId === null) {
                return false;
            }

            $row = DB::table('pmieducar.menu_tipo_usuario')
                ->where('ref_cod_tipo_usuario', $tipoId)
                ->where('menu_id', $menuId)
                ->first();

            return $row !== null && (int) ($row->cadastra ?? 0) === 1;
        } catch (\Throwable) {
            return false;
        }
    }

    private static function nivelDoUsuario(User $user): ?int
    {
        try {
            if (method_exists($user, 'getLevel')) {
                return (int) $user->getLevel();
            }
            if (isset($user->type)) {
                return (int) ($user->type->level ?? $user->type->nivel ?? 0) ?: null;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
    }
}
