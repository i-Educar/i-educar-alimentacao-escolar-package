<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Configurações persistidas do módulo (automações, janelas de refeição, RT).
 */
final class ConfigAlimentacao
{
    /** @var array<string, mixed>|null */
    private static ?array $cache = null;

    /** @return array<string, mixed> */
    public static function defaults(): array
    {
        return [
            'montar_cardapio' => (bool) config('alimentacao.montar_cardapio', true),
            'bandejas_automaticas' => (bool) config('alimentacao.bandejas_automaticas', true),
            'estimar_porcoes_ao_lancar' => (bool) config('alimentacao.estimar_porcoes_ao_lancar', true),
            'unidade_so_registra_fatos' => (bool) config('alimentacao.unidade_so_registra_fatos', true),
            'alerta_horario_turma' => (bool) config('alimentacao.alerta_horario_turma', true),
            'porcoes_padrao' => (int) config('alimentacao.porcoes_padrao', 100),
            'lanches_por_dia' => (int) config('alimentacao.lanches_por_dia', 2),
            'slots_refeicao' => config('alimentacao.slots_refeicao', ['cafe', 'almoco', 'lanche', 'lanche_2']),
            'janelas' => config('alimentacao.janelas', [
                'cafe' => ['inicio' => '07:00', 'fim' => '09:00'],
                'almoco' => ['inicio' => '11:00', 'fim' => '13:30'],
                'lanche' => ['inicio' => '09:00', 'fim' => '10:30'],
                'lanche_2' => ['inicio' => '14:00', 'fim' => '16:00'],
                'jantar' => ['inicio' => '17:30', 'fim' => '19:30'],
            ]),
            'rt_nome' => (string) config('alimentacao.rt_nome', 'Nutricionista responsável (PoC Laguna)'),
            'rt_registro' => (string) config('alimentacao.rt_registro', 'CRN-10 0000'),
            'rt_cargo' => (string) config('alimentacao.rt_cargo', 'Nutricionista responsável técnico'),
            'rt_email' => (string) config('alimentacao.rt_email', 'alimentacao@laguna.sc.gov.br'),
        ];
    }

    /** @return array<string, mixed> */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        $base = self::defaults();
        if (! Schema::hasTable('alimentacao.configuracao')) {
            return self::$cache = $base;
        }

        try {
            $rows = DB::table('alimentacao.configuracao')->pluck('valor', 'chave');
            foreach ($rows as $chave => $valor) {
                $decoded = json_decode((string) $valor, true);
                $base[$chave] = $decoded !== null || $valor === 'null' ? $decoded : $valor;
            }
        } catch (\Throwable) {
            // mantém defaults
        }

        return self::$cache = $base;
    }

    public static function get(string $chave, mixed $padrao = null): mixed
    {
        $all = self::all();

        return $all[$chave] ?? $padrao;
    }

    /** @param  array<string, mixed>  $dados */
    public static function salvar(array $dados): void
    {
        if (! Schema::hasTable('alimentacao.configuracao')) {
            throw new \RuntimeException('Tabela alimentacao.configuracao ausente — rode migrate.');
        }

        foreach ($dados as $chave => $valor) {
            DB::table('alimentacao.configuracao')->updateOrInsert(
                ['chave' => (string) $chave],
                [
                    'valor' => is_string($valor) ? $valor : json_encode($valor, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }
        self::$cache = null;
    }

    /** @return list<string> */
    public static function slotsAtivos(): array
    {
        $slots = self::get('slots_refeicao', ['cafe', 'almoco', 'lanche', 'lanche_2']);
        if (! is_array($slots) || $slots === []) {
            $n = max(1, (int) self::get('lanches_por_dia', 2));
            $slots = ['cafe', 'almoco', 'lanche'];
            for ($i = 2; $i <= $n; $i++) {
                $slots[] = 'lanche_'.$i;
            }
        }

        return array_values(array_unique(array_map('strval', $slots)));
    }

    /** @return array<string, array{rotulo:string,icone:string,meta:string}> */
    public static function slotsUi(): array
    {
        $meta = [
            'cafe' => ['rotulo' => 'Café da manhã', 'icone' => 'fa-coffee', 'meta' => 'Começo do dia'],
            'almoco' => ['rotulo' => 'Almoço', 'icone' => 'fa-cutlery', 'meta' => 'Refeição principal'],
            'lanche' => ['rotulo' => 'Lanche', 'icone' => 'fa-lemon-o', 'meta' => 'Intervalo'],
            'lanche_2' => ['rotulo' => 'Lanche 2', 'icone' => 'fa-lemon-o', 'meta' => '2º intervalo / tarde'],
            'lanche_3' => ['rotulo' => 'Lanche 3', 'icone' => 'fa-lemon-o', 'meta' => '3º intervalo'],
            'jantar' => ['rotulo' => 'Jantar', 'icone' => 'fa-moon-o', 'meta' => 'Período noturno'],
        ];
        $out = [];
        foreach (self::slotsAtivos() as $cod) {
            if (str_starts_with($cod, 'lanche_') && ! isset($meta[$cod])) {
                $n = substr($cod, 7);
                $meta[$cod] = ['rotulo' => 'Lanche '.$n, 'icone' => 'fa-lemon-o', 'meta' => 'Intervalo '.$n];
            }
            $out[$cod] = $meta[$cod] ?? ['rotulo' => ucfirst($cod), 'icone' => 'fa-cutlery', 'meta' => ''];
        }

        return $out;
    }

    /** @return array{nome:string,registro:string,cargo:string,email:string}|null */
    public static function responsavelTecnico(): ?array
    {
        if (Schema::hasTable('alimentacao.responsavel_tecnico')) {
            try {
                $row = DB::table('alimentacao.responsavel_tecnico')
                    ->where('ativo', true)
                    ->orderByDesc('padrao')
                    ->orderBy('id')
                    ->first();
                if ($row !== null && trim((string) $row->nome) !== '') {
                    return [
                        'nome' => (string) $row->nome,
                        'registro' => (string) ($row->registro ?? ''),
                        'cargo' => (string) ($row->cargo ?? 'Nutricionista responsável técnico'),
                        'email' => (string) ($row->email ?? ''),
                    ];
                }
            } catch (\Throwable) {
            }
        }

        $nome = trim((string) self::get('rt_nome', ''));
        if ($nome === '') {
            return null;
        }

        return [
            'nome' => $nome,
            'registro' => (string) self::get('rt_registro', ''),
            'cargo' => (string) self::get('rt_cargo', 'Nutricionista responsável técnico'),
            'email' => (string) self::get('rt_email', ''),
        ];
    }

    /** @param  array{nome:string,registro?:string,cargo?:string,email?:string}  $dados */
    public static function salvarResponsavelTecnico(array $dados): void
    {
        $nome = trim((string) ($dados['nome'] ?? ''));
        if ($nome === '') {
            throw new \InvalidArgumentException('Informe o nome do responsável técnico.');
        }

        self::salvar([
            'rt_nome' => $nome,
            'rt_registro' => trim((string) ($dados['registro'] ?? '')),
            'rt_cargo' => trim((string) ($dados['cargo'] ?? 'Nutricionista responsável técnico')),
            'rt_email' => trim((string) ($dados['email'] ?? '')),
        ]);

        if (! Schema::hasTable('alimentacao.responsavel_tecnico')) {
            return;
        }

        DB::table('alimentacao.responsavel_tecnico')->where('padrao', true)->update(['padrao' => false]);
        $existente = DB::table('alimentacao.responsavel_tecnico')->where('padrao', true)->first()
            ?? DB::table('alimentacao.responsavel_tecnico')->orderBy('id')->first();

        $payload = [
            'nome' => $nome,
            'registro' => trim((string) ($dados['registro'] ?? '')) ?: null,
            'cargo' => trim((string) ($dados['cargo'] ?? '')) ?: 'Nutricionista responsável técnico',
            'email' => trim((string) ($dados['email'] ?? '')) ?: null,
            'ativo' => true,
            'padrao' => true,
            'updated_at' => now(),
        ];

        if ($existente !== null) {
            DB::table('alimentacao.responsavel_tecnico')->where('id', $existente->id)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('alimentacao.responsavel_tecnico')->insert($payload);
        }
    }
}
