<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Console;

use iEducar\Packages\BuritiAlimentacaoEscolar\Services\CardapioDia;
use iEducar\Packages\BuritiAlimentacaoEscolar\Services\ConfigAlimentacao;
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
            ['refeicao' => 'cafe', 'itens' => ['pão', 'leite', 'fruta']],
            ['refeicao' => 'almoco', 'itens' => ['arroz', 'feijão', 'carne', 'salada', 'suco']],
            ['refeicao' => 'lanche', 'itens' => ['vitamina', 'biscoito']],
        ];

        $escolasMapa = [$escola, 'EMEF Norte', 'EMEI Lagoa', 'EMEF Ribeirão Pequeno'];
        if (Schema::hasTable('pmieducar.escola')) {
            $nomes = DB::table('pmieducar.escola as e')
                ->join('cadastro.juridica as j', 'j.idpes', '=', 'e.ref_idpes')
                ->where('e.ativo', 1)
                ->orderBy('e.cod_escola')
                ->limit(8)
                ->pluck('j.fantasia')
                ->all();
            if ($nomes !== []) {
                $escolasMapa = $nomes;
            }
        }

        $cardapio = new CardapioDia($data, $refeicoes, $escola);
        $linhasMapa = [];
        foreach ($escolasMapa as $i => $nomeEscola) {
            $linhasMapa[] = [
                'escola' => $nomeEscola,
                'refeicao' => 'almoco',
                'quantidade' => 80 + ($i * 15),
            ];
            $linhasMapa[] = [
                'escola' => $nomeEscola,
                'refeicao' => 'cafe',
                'quantidade' => 60 + ($i * 10),
            ];
        }
        $mapa = MapaDistribuicao::consolidar($linhasMapa);

        $dir = storage_path('app/alimentacao-onda4');
        if (! is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        file_put_contents($dir.'/cardapio.json', json_encode($cardapio->toArray(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents($dir.'/mapa.json', json_encode($mapa, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info('Onda 4 — alimentação demo');
        $this->line('Destino: '.$dir);
        $this->line(sprintf('  ✓ cardápio %s (%d itens)', $data, $cardapio->totalItens()));

        try {
            $rt = ConfigAlimentacao::responsavelTecnico();
            if ($rt === null || trim((string) ($rt['nome'] ?? '')) === '') {
                ConfigAlimentacao::salvarResponsavelTecnico([
                    'nome' => (string) config('alimentacao.rt_nome', 'Nutricionista responsável (PoC Laguna)'),
                    'registro' => (string) config('alimentacao.rt_registro', 'CRN-10 0000'),
                    'cargo' => (string) config('alimentacao.rt_cargo', 'Nutricionista responsável técnico'),
                    'email' => (string) config('alimentacao.rt_email', 'alimentacao@laguna.sc.gov.br'),
                ]);
                $this->line('  ✓ RT PoC gravado (ajuste em Configurações → Módulos)');
            }
        } catch (\Throwable $e) {
            $this->warn('  ! RT não persistido: '.$e->getMessage());
        }

        if (Schema::hasTable('alimentacao.cardapio_dia')) {
            foreach ($escolasMapa as $nomeEscola) {
                DB::table('alimentacao.cardapio_dia')->updateOrInsert(
                    ['data' => $data, 'escola' => $nomeEscola],
                    [
                        'refeicoes' => json_encode($refeicoes, JSON_UNESCAPED_UNICODE),
                        'updated_at' => now(),
                        'created_at' => now(),
                    ],
                );
            }
            if (Schema::hasTable('alimentacao.restricao_aluno')) {
                foreach ([['leite'], ['glúten'], ['ovo'], ['amendoim']] as $i => [$restricao]) {
                    DB::table('alimentacao.restricao_aluno')->updateOrInsert(
                        ['ref_cod_aluno' => $i + 1, 'restricao' => $restricao],
                        ['updated_at' => now(), 'created_at' => now()],
                    );
                }
            }
            $this->line('  ✓ persistido em alimentacao.cardapio_dia ('.count($escolasMapa).' escolas)');
        } else {
            $this->warn('  ! schema alimentacao ausente — rode migrate com PackageTenant liberado');
        }

        $this->info('Gate Onda 4.A (alimentação demo) OK.');

        return self::SUCCESS;
    }
}
