<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('alimentacao.item')) {
            Schema::create('alimentacao.item', function (Blueprint $table): void {
                $table->increments('id');
                $table->string('nome', 160);
                $table->string('categoria', 40)->default('in_natura'); // in_natura|minimamente|processado|ultraprocessado
                $table->string('grupo_pnae', 40)->default('outro'); // cereal|leguminosa|hortalica|fruta|proteina|laticinio|outro
                $table->boolean('origem_af')->default(false); // agricultura familiar
                $table->boolean('ativo')->default(true);
                $table->string('unidade', 20)->default('porção');
                $table->jsonb('alergenos')->nullable();
                $table->timestamps();
                $table->unique('nome');
            });
        }

        if (Schema::hasTable('alimentacao.item') && (int) DB::table('alimentacao.item')->count() === 0) {
            $agora = now();
            $seed = [
                ['arroz', 'in_natura', 'cereal', true],
                ['feijão', 'in_natura', 'leguminosa', true],
                ['feijao', 'in_natura', 'leguminosa', true],
                ['salada', 'in_natura', 'hortalica', true],
                ['fruta', 'in_natura', 'fruta', true],
                ['banana', 'in_natura', 'fruta', true],
                ['maçã', 'in_natura', 'fruta', true],
                ['leite', 'minimamente', 'laticinio', false],
                ['pão', 'processado', 'cereal', false],
                ['pao', 'processado', 'cereal', false],
                ['ovos', 'in_natura', 'proteina', true],
                ['carne', 'minimamente', 'proteina', false],
                ['suco natural', 'minimamente', 'fruta', true],
                ['biscoito', 'ultraprocessado', 'outro', false],
                ['refrigerante', 'ultraprocessado', 'outro', false],
            ];
            foreach ($seed as [$nome, $cat, $grupo, $af]) {
                DB::table('alimentacao.item')->insert([
                    'nome' => $nome,
                    'categoria' => $cat,
                    'grupo_pnae' => $grupo,
                    'origem_af' => $af,
                    'ativo' => true,
                    'unidade' => 'porção',
                    'alergenos' => json_encode([]),
                    'created_at' => $agora,
                    'updated_at' => $agora,
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alimentacao.item');
    }
};
