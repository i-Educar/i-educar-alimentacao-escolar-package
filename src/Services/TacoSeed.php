<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 5.2.10 — Seed mínimo TACO/IBGE (~40 alimentos) em alimentacao.item.
 * Valores nutricionais aproximados por 100 g (PoC Laguna).
 */
final class TacoSeed
{
    /**
     * @return array{inseridos:int,atualizados:int,pulados:int}
     */
    public function aplicar(): array
    {
        if (! Schema::hasTable('alimentacao.item')) {
            throw new \RuntimeException('Tabela alimentacao.item ausente.');
        }

        $stats = ['inseridos' => 0, 'atualizados' => 0, 'pulados' => 0];
        $agora = now();

        foreach ($this->alimentos() as $a) {
            $existente = DB::table('alimentacao.item')->where('nome', $a['nome'])->first();
            $payload = [
                'categoria' => $a['categoria'],
                'grupo_pnae' => $a['grupo_pnae'],
                'origem_af' => false,
                'ativo' => true,
                'unidade' => $a['unidade'],
                'kcal_porcao' => $a['kcal'],
                'proteina_g' => $a['proteina'],
                'carboidrato_g' => $a['carbo'],
                'lipidio_g' => $a['lipidio'],
                'fibra_g' => $a['fibra'],
                'sodio_mg' => $a['sodio'],
                'peso_porcao_g' => 100,
                'padrao_pnae' => true,
                'updated_at' => $agora,
            ];

            if ($existente) {
                DB::table('alimentacao.item')->where('id', $existente->id)->update($payload);
                $stats['atualizados']++;
                continue;
            }

            DB::table('alimentacao.item')->insert($payload + [
                'nome' => $a['nome'],
                'created_at' => $agora,
            ]);
            $stats['inseridos']++;
        }

        return $stats;
    }

    /**
     * @return list<array{
     *   nome:string,categoria:string,grupo_pnae:string,unidade:string,
     *   kcal:float,proteina:float,carbo:float,lipidio:float,fibra:float,sodio:float
     * }>
     */
    public function alimentos(): array
    {
        // Fonte de referência: Tabela TACO (aproximação PoC / 100 g)
        return [
            ['nome' => 'Arroz branco cozido', 'categoria' => 'cereal', 'grupo_pnae' => 'cereal', 'unidade' => 'g', 'kcal' => 128, 'proteina' => 2.5, 'carbo' => 28.1, 'lipidio' => 0.2, 'fibra' => 1.6, 'sodio' => 1],
            ['nome' => 'Arroz integral cozido', 'categoria' => 'cereal', 'grupo_pnae' => 'cereal', 'unidade' => 'g', 'kcal' => 124, 'proteina' => 2.6, 'carbo' => 25.8, 'lipidio' => 1.0, 'fibra' => 2.7, 'sodio' => 1],
            ['nome' => 'Feijão carioca cozido', 'categoria' => 'leguminosa', 'grupo_pnae' => 'leguminosa', 'unidade' => 'g', 'kcal' => 76, 'proteina' => 4.8, 'carbo' => 13.6, 'lipidio' => 0.5, 'fibra' => 8.5, 'sodio' => 2],
            ['nome' => 'Feijão preto cozido', 'categoria' => 'leguminosa', 'grupo_pnae' => 'leguminosa', 'unidade' => 'g', 'kcal' => 77, 'proteina' => 4.5, 'carbo' => 14.0, 'lipidio' => 0.5, 'fibra' => 8.4, 'sodio' => 2],
            ['nome' => 'Lentilha cozida', 'categoria' => 'leguminosa', 'grupo_pnae' => 'leguminosa', 'unidade' => 'g', 'kcal' => 93, 'proteina' => 6.3, 'carbo' => 16.3, 'lipidio' => 0.5, 'fibra' => 7.9, 'sodio' => 1],
            ['nome' => 'Grão-de-bico cozido', 'categoria' => 'leguminosa', 'grupo_pnae' => 'leguminosa', 'unidade' => 'g', 'kcal' => 121, 'proteina' => 7.0, 'carbo' => 19.0, 'lipidio' => 2.0, 'fibra' => 5.0, 'sodio' => 5],
            ['nome' => 'Milho cozido', 'categoria' => 'cereal', 'grupo_pnae' => 'cereal', 'unidade' => 'g', 'kcal' => 96, 'proteina' => 3.2, 'carbo' => 17.1, 'lipidio' => 2.0, 'fibra' => 4.0, 'sodio' => 1],
            ['nome' => 'Macarrão cozido', 'categoria' => 'cereal', 'grupo_pnae' => 'cereal', 'unidade' => 'g', 'kcal' => 131, 'proteina' => 5.0, 'carbo' => 25.0, 'lipidio' => 0.9, 'fibra' => 1.5, 'sodio' => 1],
            ['nome' => 'Batata inglesa cozida', 'categoria' => 'raiz', 'grupo_pnae' => 'hortalica', 'unidade' => 'g', 'kcal' => 52, 'proteina' => 1.2, 'carbo' => 11.9, 'lipidio' => 0.1, 'fibra' => 1.3, 'sodio' => 2],
            ['nome' => 'Batata-doce cozida', 'categoria' => 'raiz', 'grupo_pnae' => 'hortalica', 'unidade' => 'g', 'kcal' => 77, 'proteina' => 0.6, 'carbo' => 18.4, 'lipidio' => 0.1, 'fibra' => 2.2, 'sodio' => 3],
            ['nome' => 'Mandioca cozida', 'categoria' => 'raiz', 'grupo_pnae' => 'hortalica', 'unidade' => 'g', 'kcal' => 125, 'proteina' => 0.6, 'carbo' => 30.1, 'lipidio' => 0.3, 'fibra' => 1.6, 'sodio' => 1],
            ['nome' => 'Inhame cozido', 'categoria' => 'raiz', 'grupo_pnae' => 'hortalica', 'unidade' => 'g', 'kcal' => 97, 'proteina' => 1.7, 'carbo' => 23.0, 'lipidio' => 0.2, 'fibra' => 1.5, 'sodio' => 2],
            ['nome' => 'Abóbora cozida', 'categoria' => 'hortalica', 'grupo_pnae' => 'hortalica', 'unidade' => 'g', 'kcal' => 14, 'proteina' => 0.6, 'carbo' => 2.5, 'lipidio' => 0.1, 'fibra' => 1.5, 'sodio' => 1],
            ['nome' => 'Cenoura crua', 'categoria' => 'hortalica', 'grupo_pnae' => 'hortalica', 'unidade' => 'g', 'kcal' => 34, 'proteina' => 1.3, 'carbo' => 7.7, 'lipidio' => 0.2, 'fibra' => 3.2, 'sodio' => 3],
            ['nome' => 'Tomate cru', 'categoria' => 'hortalica', 'grupo_pnae' => 'hortalica', 'unidade' => 'g', 'kcal' => 15, 'proteina' => 1.1, 'carbo' => 3.1, 'lipidio' => 0.2, 'fibra' => 1.2, 'sodio' => 1],
            ['nome' => 'Alface', 'categoria' => 'hortalica', 'grupo_pnae' => 'hortalica', 'unidade' => 'g', 'kcal' => 11, 'proteina' => 1.3, 'carbo' => 1.7, 'lipidio' => 0.2, 'fibra' => 1.5, 'sodio' => 4],
            ['nome' => 'Couve refogada', 'categoria' => 'hortalica', 'grupo_pnae' => 'hortalica', 'unidade' => 'g', 'kcal' => 90, 'proteina' => 1.8, 'carbo' => 8.0, 'lipidio' => 6.0, 'fibra' => 3.0, 'sodio' => 10],
            ['nome' => 'Brócolis cozido', 'categoria' => 'hortalica', 'grupo_pnae' => 'hortalica', 'unidade' => 'g', 'kcal' => 25, 'proteina' => 2.1, 'carbo' => 4.4, 'lipidio' => 0.4, 'fibra' => 3.4, 'sodio' => 5],
            ['nome' => 'Banana nanica', 'categoria' => 'fruta', 'grupo_pnae' => 'fruta', 'unidade' => 'g', 'kcal' => 92, 'proteina' => 1.4, 'carbo' => 23.8, 'lipidio' => 0.1, 'fibra' => 1.9, 'sodio' => 0],
            ['nome' => 'Maçã com casca', 'categoria' => 'fruta', 'grupo_pnae' => 'fruta', 'unidade' => 'g', 'kcal' => 56, 'proteina' => 0.3, 'carbo' => 15.2, 'lipidio' => 0.2, 'fibra' => 1.3, 'sodio' => 0],
            ['nome' => 'Laranja pera', 'categoria' => 'fruta', 'grupo_pnae' => 'fruta', 'unidade' => 'g', 'kcal' => 37, 'proteina' => 1.0, 'carbo' => 8.9, 'lipidio' => 0.1, 'fibra' => 0.8, 'sodio' => 0],
            ['nome' => 'Mamão papaia', 'categoria' => 'fruta', 'grupo_pnae' => 'fruta', 'unidade' => 'g', 'kcal' => 40, 'proteina' => 0.5, 'carbo' => 10.4, 'lipidio' => 0.1, 'fibra' => 1.0, 'sodio' => 2],
            ['nome' => 'Melancia', 'categoria' => 'fruta', 'grupo_pnae' => 'fruta', 'unidade' => 'g', 'kcal' => 33, 'proteina' => 0.9, 'carbo' => 8.1, 'lipidio' => 0.1, 'fibra' => 0.1, 'sodio' => 1],
            ['nome' => 'Manga Tommy', 'categoria' => 'fruta', 'grupo_pnae' => 'fruta', 'unidade' => 'g', 'kcal' => 51, 'proteina' => 0.4, 'carbo' => 13.0, 'lipidio' => 0.2, 'fibra' => 1.0, 'sodio' => 1],
            ['nome' => 'Frango peito sem pele grelhado', 'categoria' => 'carne', 'grupo_pnae' => 'proteina', 'unidade' => 'g', 'kcal' => 159, 'proteina' => 32.0, 'carbo' => 0.0, 'lipidio' => 3.2, 'fibra' => 0.0, 'sodio' => 56],
            ['nome' => 'Carne bovina patinho grelhado', 'categoria' => 'carne', 'grupo_pnae' => 'proteina', 'unidade' => 'g', 'kcal' => 219, 'proteina' => 32.5, 'carbo' => 0.0, 'lipidio' => 9.0, 'fibra' => 0.0, 'sodio' => 50],
            ['nome' => 'Ovo de galinha cozido', 'categoria' => 'ovo', 'grupo_pnae' => 'proteina', 'unidade' => 'un', 'kcal' => 146, 'proteina' => 13.3, 'carbo' => 0.6, 'lipidio' => 9.5, 'fibra' => 0.0, 'sodio' => 146],
            ['nome' => 'Peixe tilápia grelhada', 'categoria' => 'peixe', 'grupo_pnae' => 'proteina', 'unidade' => 'g', 'kcal' => 96, 'proteina' => 20.0, 'carbo' => 0.0, 'lipidio' => 1.5, 'fibra' => 0.0, 'sodio' => 50],
            ['nome' => 'Leite integral UHT', 'categoria' => 'laticinio', 'grupo_pnae' => 'laticinio', 'unidade' => 'ml', 'kcal' => 61, 'proteina' => 3.0, 'carbo' => 4.8, 'lipidio' => 3.3, 'fibra' => 0.0, 'sodio' => 50],
            ['nome' => 'Iogurte natural', 'categoria' => 'laticinio', 'grupo_pnae' => 'laticinio', 'unidade' => 'g', 'kcal' => 51, 'proteina' => 4.1, 'carbo' => 1.9, 'lipidio' => 3.0, 'fibra' => 0.0, 'sodio' => 52],
            ['nome' => 'Queijo mussarela', 'categoria' => 'laticinio', 'grupo_pnae' => 'laticinio', 'unidade' => 'g', 'kcal' => 330, 'proteina' => 22.6, 'carbo' => 3.0, 'lipidio' => 25.0, 'fibra' => 0.0, 'sodio' => 581],
            ['nome' => 'Pão francês', 'categoria' => 'panificado', 'grupo_pnae' => 'cereal', 'unidade' => 'g', 'kcal' => 300, 'proteina' => 8.0, 'carbo' => 58.6, 'lipidio' => 3.1, 'fibra' => 2.3, 'sodio' => 648],
            ['nome' => 'Pão de forma integral', 'categoria' => 'panificado', 'grupo_pnae' => 'cereal', 'unidade' => 'g', 'kcal' => 253, 'proteina' => 9.4, 'carbo' => 42.5, 'lipidio' => 3.5, 'fibra' => 6.9, 'sodio' => 506],
            ['nome' => 'Biscoito cream cracker', 'categoria' => 'panificado', 'grupo_pnae' => 'cereal', 'unidade' => 'g', 'kcal' => 432, 'proteina' => 10.0, 'carbo' => 68.0, 'lipidio' => 14.0, 'fibra' => 2.5, 'sodio' => 700],
            ['nome' => 'Azeite de oliva', 'categoria' => 'gordura', 'grupo_pnae' => 'gordura', 'unidade' => 'ml', 'kcal' => 884, 'proteina' => 0.0, 'carbo' => 0.0, 'lipidio' => 100.0, 'fibra' => 0.0, 'sodio' => 0],
            ['nome' => 'Óleo de soja', 'categoria' => 'gordura', 'grupo_pnae' => 'gordura', 'unidade' => 'ml', 'kcal' => 884, 'proteina' => 0.0, 'carbo' => 0.0, 'lipidio' => 100.0, 'fibra' => 0.0, 'sodio' => 0],
            ['nome' => 'Açúcar cristal', 'categoria' => 'doce', 'grupo_pnae' => 'doce', 'unidade' => 'g', 'kcal' => 387, 'proteina' => 0.0, 'carbo' => 99.9, 'lipidio' => 0.0, 'fibra' => 0.0, 'sodio' => 0],
            ['nome' => 'Mel de abelha', 'categoria' => 'doce', 'grupo_pnae' => 'doce', 'unidade' => 'g', 'kcal' => 309, 'proteina' => 0.4, 'carbo' => 84.0, 'lipidio' => 0.0, 'fibra' => 0.2, 'sodio' => 6],
            ['nome' => 'Café coado sem açúcar', 'categoria' => 'bebida', 'grupo_pnae' => 'bebida', 'unidade' => 'ml', 'kcal' => 2, 'proteina' => 0.1, 'carbo' => 0.3, 'lipidio' => 0.0, 'fibra' => 0.0, 'sodio' => 1],
            ['nome' => 'Suco de laranja natural', 'categoria' => 'bebida', 'grupo_pnae' => 'fruta', 'unidade' => 'ml', 'kcal' => 41, 'proteina' => 0.7, 'carbo' => 9.5, 'lipidio' => 0.1, 'fibra' => 0.1, 'sodio' => 0],
        ];
    }
}
