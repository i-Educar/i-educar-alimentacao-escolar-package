<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Confronta janelas de refeição com o horário escolar da turma.
 */
final class HorarioCardapio
{
    /**
     * @param  list<array{refeicao:string,itens?:list<string>}>  $refeicoes
     * @return list<array{nivel:string,codigo:string,mensagem:string}>
     */
    public static function avaliar(?int $turmaId, array $refeicoes, ?string $escola = null): array
    {
        if (! (bool) ConfigAlimentacao::get('alerta_horario_turma', true)) {
            return [];
        }

        $janelas = ConfigAlimentacao::get('janelas', []);
        if (! is_array($janelas) || $janelas === []) {
            return [];
        }

        $horarios = self::horariosTurma($turmaId, $escola);
        if ($horarios === []) {
            if ($turmaId !== null && $turmaId > 0) {
                return [[
                    'nivel' => 'alerta',
                    'codigo' => 'horario.turma_sem_horario',
                    'mensagem' => 'Turma sem horário cadastrado (hora_inicial/hora_final) — não foi possível validar o cardápio contra o tempo escolar.',
                ]];
            }

            return [];
        }

        $alertas = [];
        foreach ($refeicoes as $ref) {
            $cod = self::normalizarSlot((string) ($ref['refeicao'] ?? ''));
            if ($cod === '' || empty($ref['itens'])) {
                continue;
            }
            $janela = $janelas[$cod] ?? (str_starts_with($cod, 'lanche') ? ($janelas['lanche'] ?? null) : null);
            if (! is_array($janela) || empty($janela['inicio']) || empty($janela['fim'])) {
                continue;
            }

            $inicioRef = self::minutos((string) $janela['inicio']);
            $fimRef = self::minutos((string) $janela['fim']);
            if ($inicioRef === null || $fimRef === null) {
                continue;
            }

            $encaixa = false;
            foreach ($horarios as $h) {
                if (self::sobrepoe($inicioRef, $fimRef, $h['inicio'], $h['fim'])) {
                    $encaixa = true;
                    break;
                }
            }

            if (! $encaixa) {
                $rotulo = ConfigAlimentacao::slotsUi()[$cod]['rotulo'] ?? $cod;
                $faixas = implode(', ', array_map(
                    static fn (array $h): string => self::fmt($h['inicio']).'–'.self::fmt($h['fim']),
                    $horarios
                ));
                $alertas[] = [
                    'nivel' => 'erro',
                    'codigo' => 'horario.fora',
                    'mensagem' => "Previsão fora do horário escolar: «{$rotulo}» ({$janela['inicio']}–{$janela['fim']}) não coincide com o tempo da turma ({$faixas}).",
                ];
            }
        }

        return $alertas;
    }

    /**
     * @return list<array{inicio:int,fim:int,rotulo:string}>
     */
    public static function horariosTurma(?int $turmaId, ?string $escola = null): array
    {
        if (! Schema::hasTable('pmieducar.turma')) {
            return [];
        }

        try {
            $q = DB::table('pmieducar.turma')->where('ativo', 1);
            if ($turmaId !== null && $turmaId > 0) {
                $q->where('cod_turma', $turmaId);
            } elseif ($escola !== null && $escola !== '' && $escola !== 'Rede'
                && Schema::hasTable('pmieducar.escola') && Schema::hasTable('cadastro.juridica')) {
                $cod = DB::table('pmieducar.escola')
                    ->join('cadastro.juridica', 'cadastro.juridica.idpes', '=', 'pmieducar.escola.ref_idpes')
                    ->where('pmieducar.escola.ativo', 1)
                    ->where('cadastro.juridica.fantasia', $escola)
                    ->value('pmieducar.escola.cod_escola');
                if ($cod === null) {
                    return [];
                }
                $q->where('ref_ref_cod_escola', (int) $cod)->where('ano', (int) date('Y'))->limit(40);
            } else {
                return [];
            }

            $rows = $q->get(['nm_turma', 'hora_inicial', 'hora_final', 'hora_inicial_matutino', 'hora_final_matutino', 'hora_inicial_vespertino', 'hora_final_vespertino']);
        } catch (\Throwable) {
            return [];
        }

        $out = [];
        foreach ($rows as $r) {
            foreach ([
                [(string) ($r->hora_inicial ?? ''), (string) ($r->hora_final ?? '')],
                [(string) ($r->hora_inicial_matutino ?? ''), (string) ($r->hora_final_matutino ?? '')],
                [(string) ($r->hora_inicial_vespertino ?? ''), (string) ($r->hora_final_vespertino ?? '')],
            ] as [$a, $b]) {
                $ia = self::minutos($a);
                $ib = self::minutos($b);
                if ($ia === null || $ib === null || $ib <= $ia) {
                    continue;
                }
                $out[] = [
                    'inicio' => $ia,
                    'fim' => $ib,
                    'rotulo' => (string) ($r->nm_turma ?? 'turma'),
                ];
            }
        }

        return $out;
    }

    public static function normalizarSlot(string $cod): string
    {
        $cod = mb_strtolower(trim($cod));
        if ($cod === 'lanches') {
            return 'lanche';
        }

        return $cod;
    }

    private static function minutos(string $hora): ?int
    {
        $hora = trim($hora);
        if ($hora === '') {
            return null;
        }
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $hora, $m)) {
            return ((int) $m[1]) * 60 + (int) $m[2];
        }

        return null;
    }

    private static function sobrepoe(int $a0, int $a1, int $b0, int $b1): bool
    {
        return $a0 < $b1 && $b0 < $a1;
    }

    private static function fmt(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }
}
