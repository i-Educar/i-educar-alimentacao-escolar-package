<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nutrição/pesos nos itens; escopo de cardápio (rede/escola/turma/segmento);
 * perfil escolar de porções servidas para balanço e previsão de compras.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS alimentacao');

        if (Schema::hasTable('alimentacao.item')) {
            foreach ([
                'peso_porcao_g numeric(10,2) NULL',
                'kcal_porcao numeric(10,2) NULL',
                'proteina_g numeric(10,2) NULL',
                'carboidrato_g numeric(10,2) NULL',
                'lipidio_g numeric(10,2) NULL',
                'fibra_g numeric(10,2) NULL',
                'sodio_mg numeric(10,2) NULL',
                'custo_unitario numeric(12,4) NULL',
                'padrao_pnae boolean NOT NULL DEFAULT true',
            ] as $col) {
                DB::statement("ALTER TABLE alimentacao.item ADD COLUMN IF NOT EXISTS {$col}");
            }
        }

        if (Schema::hasTable('alimentacao.cardapio_dia')) {
            foreach ([
                "escopo varchar(20) NOT NULL DEFAULT 'escola'",
                'segmento varchar(80) NULL',
                'periodo varchar(20) NOT NULL DEFAULT \'diario\'',
                'titulo varchar(160) NULL',
                'confirmado_em timestamp NULL',
                'confirmado_por integer NULL',
            ] as $col) {
                DB::statement("ALTER TABLE alimentacao.cardapio_dia ADD COLUMN IF NOT EXISTS {$col}");
            }
            try {
                DB::statement('DROP INDEX IF EXISTS cardapio_dia_data_escola_turma_uidx');
            } catch (\Throwable) {
            }
            try {
                DB::statement(
                    'CREATE UNIQUE INDEX IF NOT EXISTS cardapio_dia_escopo_uidx
                     ON alimentacao.cardapio_dia (data, escola, COALESCE(turma, \'\'), COALESCE(segmento, \'\'), escopo)'
                );
            } catch (\Throwable) {
            }
        }

        if (! Schema::hasTable('alimentacao.perfil_servico')) {
            Schema::create('alimentacao.perfil_servico', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->date('data');
                $table->string('escola', 160);
                $table->string('segmento', 80)->nullable();
                $table->string('turma', 160)->nullable();
                $table->unsignedBigInteger('cardapio_id')->nullable();
                $table->string('refeicao', 40);
                $table->unsignedInteger('porcoes_previstas')->default(0);
                $table->unsignedInteger('porcoes_servidas')->default(0);
                $table->text('observacao')->nullable();
                $table->timestamp('confirmado_em')->nullable();
                $table->unsignedInteger('confirmado_por')->nullable();
                $table->timestamps();
                $table->index(['data', 'escola']);
                $table->unique(['data', 'escola', 'refeicao', 'turma', 'segmento'], 'perfil_servico_uidx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alimentacao.perfil_servico');
    }
};
