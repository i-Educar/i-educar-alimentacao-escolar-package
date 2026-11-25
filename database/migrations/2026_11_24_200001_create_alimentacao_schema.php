<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Schema próprio alimentacao.* (PADRAO-PACOTE-2.12 §4).
 * Não duplica aluno/pessoa do núcleo — usa ref_cod_aluno lógico.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS alimentacao');

        if (! Schema::hasTable('alimentacao.cardapio_dia')) {
            Schema::create('alimentacao.cardapio_dia', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->date('data');
                $table->string('escola', 160)->default('Rede');
                $table->jsonb('refeicoes');
                $table->timestamps();
                $table->unique(['data', 'escola']);
            });
        }

        if (! Schema::hasTable('alimentacao.restricao_aluno')) {
            Schema::create('alimentacao.restricao_aluno', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->unsignedBigInteger('ref_cod_aluno');
                $table->string('restricao', 120);
                $table->timestamps();
                $table->index('ref_cod_aluno');
                $table->unique(['ref_cod_aluno', 'restricao']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alimentacao.restricao_aluno');
        Schema::dropIfExists('alimentacao.cardapio_dia');
        // Não dropa o schema se outros objetos existirem — só as tabelas do pacote.
    }
};
