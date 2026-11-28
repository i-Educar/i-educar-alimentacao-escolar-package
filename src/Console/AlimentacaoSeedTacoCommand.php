<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Console;

use iEducar\Packages\BuritiAlimentacaoEscolar\Services\TacoSeed;
use Illuminate\Console\Command;

class AlimentacaoSeedTacoCommand extends Command
{
    protected $signature = 'alimentacao:seed-taco';

    protected $description = 'Onda 7/13 — seed mínimo TACO/IBGE (~40 alimentos) em alimentacao.item';

    public function handle(TacoSeed $seed): int
    {
        try {
            $stats = $seed->aplicar();
        } catch (\Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'TACO: %d inseridos · %d atualizados · catálogo %d alimentos',
            $stats['inseridos'],
            $stats['atualizados'],
            count($seed->alimentos()),
        ));

        return self::SUCCESS;
    }
}
