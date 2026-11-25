<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Console;

use iEducar\Packages\BuritiAlimentacaoEscolar\Services\CardapioDia;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\MapaDistribuicao;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AlimentacaoDemoCommand extends Command
{
    protected $signature = 'alimentacao:demo {--data=} {--escola=Rede}';

    protected $description = 'Onda 4 — seed cardápio + mapa distribuição (PoC alimentação)';

    public function handle(): int
    {
        $data = (string) ($this->option('data') ?: date('Y-m-d'));
        $escola = (string) $this->option('escola');
        $refeicoes = [
            ['refeicao' => 'cafe', 'itens' => ['pao', 'leite']],
            ['refeicao' => 'almoco', 'itens' => ['arroz', 'feijao', 'fruta']],
        ];

        $cardapio = new CardapioDia($data, $refeicoes, $escola);
        $mapa = MapaDistribuicao::consolidar([
            ['escola' => $escola, 'refeicao' => 'almoco', 'quantidade' => 120],
            ['escola' => 'EMEF Norte', 'refeicao' => 'almoco', 'quantidade' => 80],
        ]);

        $dir = storage_path('app/alimentacao-onda4');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($dir.'/cardapio.json', json_encode($cardapio->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($dir.'/mapa.json', json_encode($mapa, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('Onda 4 — alimentação demo');
        $this->line('Destino: '.$dir);
        $this->line(sprintf('  ✓ cardápio %s (%d itens)', $data, $cardapio->totalItens()));

        if (Schema::hasTable('alimentacao.cardapio_dia')) {
            DB::table('alimentacao.cardapio_dia')->updateOrInsert(
                ['data' => $data, 'escola' => $escola],
                [
                    'refeicoes' => json_encode($refeicoes, JSON_UNESCAPED_UNICODE),
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
            if (Schema::hasTable('alimentacao.restricao_aluno')) {
                DB::table('alimentacao.restricao_aluno')->updateOrInsert(
                    ['ref_cod_aluno' => 1, 'restricao' => 'leite'],
                    ['updated_at' => now(), 'created_at' => now()],
                );
            }
            $this->line('  ✓ persistido em alimentacao.cardapio_dia');
        } else {
            $this->warn('  ! schema alimentacao ausente — rode migrate com PackageTenant liberado');
        }

        $this->info('Gate Onda 4.A (alimentação demo) OK.');

        return self::SUCCESS;
    }
}
