<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Ficha técnica de preparações (5.2.7, 5.2.8, 5.2.9)
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS alimentacao');

        if (! Schema::hasTable('alimentacao.ficha_tecnica')) {
            Schema::create('alimentacao.ficha_tecnica', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('nome', 200)->comment('Nome da preparação');
                $table->string('escola', 200)->nullable()->comment('Escola de origem');
                $table->string('categoria', 60)->default('principal')->comment('principal, acompanhamento, sobremesa, bebida');
                $table->text('modo_preparo')->nullable();
                $table->text('utensilios')->nullable();
                $table->integer('tempo_preparo_min')->nullable()->comment('Tempo de preparo em minutos');
                $table->integer('rendimento_porcoes')->nullable()->comment('Número de porções geradas');
                $table->decimal('peso_porcao_g', 10, 2)->nullable()->comment('Peso médio por porção (g)');
                $table->text('ingredientes_json')->nullable()->comment('Array de ingredientes com quantidades');
                $table->decimal('custo_total', 10, 2)->nullable();
                $table->decimal('custo_porcao', 10, 2)->nullable();
                
                // Informação nutricional (por porção)
                $table->decimal('kcal_porcao', 10, 2)->nullable();
                $table->decimal('proteina_g', 10, 2)->nullable();
                $table->decimal('carboidrato_g', 10, 2)->nullable();
                $table->decimal('lipidio_g', 10, 2)->nullable();
                $table->decimal('fibra_g', 10, 2)->nullable();
                $table->decimal('sodio_mg', 10, 2)->nullable();
                
                $table->boolean('ativo')->default(true);
                $table->integer('usuario_id')->nullable()->comment('Quem cadastrou');
                $table->timestamps();
                
                $table->index(['escola', 'ativo']);
                $table->index('categoria');
            });
        }

        // Tabela de estoque básico
        if (! Schema::hasTable('alimentacao.estoque')) {
            Schema::create('alimentacao.estoque', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('escola', 200);
                $table->integer('item_id')->comment('FK para alimentacao.item');
                $table->decimal('quantidade', 10, 2)->default(0);
                $table->string('unidade', 40)->default('kg');
                $table->date('data_atualizacao')->default(DB::raw('CURRENT_DATE'));
                $table->timestamps();
                
                $table->unique(['escola', 'item_id']);
                $table->index('escola');
            });
        }

        // Tabela de movimentações (entregas, consumo, transferências)
        if (! Schema::hasTable('alimentacao.movimentacao')) {
            Schema::create('alimentacao.movimentacao', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('escola', 200);
                $table->integer('item_id')->comment('FK para alimentacao.item');
                $table->string('tipo', 40)->comment('entrega, consumo, transferencia, ajuste');
                $table->decimal('quantidade', 10, 2);
                $table->string('unidade', 40)->default('kg');
                $table->date('data');
                $table->string('origem', 200)->nullable()->comment('Escola origem em transferência');
                $table->string('destino', 200)->nullable()->comment('Escola destino em transferência');
                $table->text('observacao')->nullable();
                $table->integer('usuario_id')->nullable();
                $table->timestamps();
                
                $table->index(['escola', 'data']);
                $table->index(['tipo', 'data']);
            });
        }

        // Tabela de sobras e desperdício
        if (! Schema::hasTable('alimentacao.sobra_registro')) {
            Schema::create('alimentacao.sobra_registro', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('escola', 200);
                $table->date('data');
                $table->string('refeicao', 60)->comment('cafe, almoco, lanche, etc');
                $table->decimal('sobra_limpa_kg', 10, 2)->nullable()->comment('Alimento não servido, mas limpo');
                $table->decimal('sobra_suja_kg', 10, 2)->nullable()->comment('Alimento não servido, contaminado');
                $table->decimal('resto_kg', 10, 2)->nullable()->comment('Alimento servido e não consumido');
                $table->integer('porcoes_produzidas')->nullable();
                $table->integer('porcoes_servidas')->nullable();
                $table->text('observacao')->nullable();
                $table->integer('usuario_id')->nullable();
                $table->timestamps();
                
                $table->index(['escola', 'data']);
                $table->index('data');
            });
        }

        // Tabela de entregas previstas (5.2.33)
        if (! Schema::hasTable('alimentacao.entrega_prevista')) {
            Schema::create('alimentacao.entrega_prevista', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('escola', 200);
                $table->date('data_prevista');
                $table->date('data_realizada')->nullable();
                $table->string('status', 40)->default('prevista')->comment('prevista, entregue, atrasada, cancelada');
                $table->text('itens_json')->nullable()->comment('Array de item_id + quantidades');
                $table->text('observacao')->nullable();
                $table->integer('usuario_id')->nullable();
                $table->timestamps();
                
                $table->index(['escola', 'data_prevista']);
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alimentacao.entrega_prevista');
        Schema::dropIfExists('alimentacao.sobra_registro');
        Schema::dropIfExists('alimentacao.movimentacao');
        Schema::dropIfExists('alimentacao.estoque');
        Schema::dropIfExists('alimentacao.ficha_tecnica');
    }
};
