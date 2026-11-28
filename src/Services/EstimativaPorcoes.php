<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Estima porções previstas a partir de matrículas / turmas / segmentos.
 */
final class EstimativaPorcoes
{
    /**
     * @param  list<string>  $segmentos
     */
    public static function estimar(string $escola, array $segmentos = [], ?string $turma = null): int
    {
        $padrao = max(1, (int) ConfigAlimentacao::get('porcoes_padrao', 100));
        if ($escola === '' || $escola === 'Rede') {
            return $padrao;
        }

        $n = self::contarMatriculas($escola, $segmentos, $turma);
        if ($n > 0) {
            return $n;
        }

        return $padrao;
    }

    /**
     * @param  list<string>  $segmentos
     */
    private static function contarMatriculas(string $escola, array $segmentos, ?string $turma): int
    {
        if (! Schema::hasTable('pmieducar.matricula')
            || ! Schema::hasTable('pmieducar.escola')
            || ! Schema::hasTable('cadastro.juridica')) {
            return 0;
        }

        try {
            $cod = DB::table('pmieducar.escola')
                ->join('cadastro.juridica', 'cadastro.juridica.idpes', '=', 'pmieducar.escola.ref_idpes')
                ->where('pmieducar.escola.ativo', 1)
                ->where('cadastro.juridica.fantasia', $escola)
                ->value('pmieducar.escola.cod_escola');
            if ($cod === null) {
                return 0;
            }

            $q = DB::table('pmieducar.matricula')
                ->where('pmieducar.matricula.ativo', 1)
                ->where('pmieducar.matricula.ref_ref_cod_escola', (int) $cod);

            if ($turma !== null && $turma !== '' && Schema::hasTable('pmieducar.turma')) {
                $q->join('pmieducar.turma', 'pmieducar.turma.cod_turma', '=', 'pmieducar.matricula.ref_cod_turma')
                    ->where('pmieducar.turma.nm_turma', $turma)
                    ->where('pmieducar.turma.ativo', 1);
            }

            // Segmentos: aproximação por nome de curso/série quando disponível
            if ($segmentos !== [] && Schema::hasTable('pmieducar.serie')) {
                $likes = [];
                foreach ($segmentos as $seg) {
                    $likes = array_merge($likes, self::likesSegmento($seg));
                }
                if ($likes !== []) {
                    if (! self::joined($q, 'pmieducar.turma')) {
                        $q->join('pmieducar.turma', 'pmieducar.turma.cod_turma', '=', 'pmieducar.matricula.ref_cod_turma');
                    }
                    $q->leftJoin('pmieducar.serie', 'pmieducar.serie.cod_serie', '=', 'pmieducar.turma.ref_ref_cod_serie')
                        ->where(function ($w) use ($likes): void {
                            foreach ($likes as $like) {
                                $w->orWhere('pmieducar.serie.nm_serie', 'ilike', $like)
                                    ->orWhere('pmieducar.turma.nm_turma', 'ilike', $like);
                            }
                        });
                }
            }

            return (int) $q->count('pmieducar.matricula.cod_matricula');
        } catch (\Throwable) {
            return 0;
        }
    }

    /** @return list<string> */
    private static function likesSegmento(string $seg): array
    {
        return match ($seg) {
            'infantil' => ['%infantil%', '%berç%', '%pre%', '%pré%'],
            'fundamental_1' => ['%1º%', '%2º%', '%3º%', '%4º%', '%5º%', '%1o%', '%5o%'],
            'fundamental_2' => ['%6º%', '%7º%', '%8º%', '%9º%', '%6o%', '%9o%'],
            'medio' => ['%médio%', '%medio%', '%1ª%', '%2ª%', '%3ª%'],
            'eja' => ['%eja%'],
            'aee' => ['%aee%', '%especial%'],
            default => [],
        };
    }

    private static function joined(object $q, string $table): bool
    {
        try {
            $joins = $q->joins ?? [];
            foreach ($joins as $j) {
                if (isset($j->table) && str_contains((string) $j->table, $table)) {
                    return true;
                }
            }
        } catch (\Throwable) {
        }

        return false;
    }
}
