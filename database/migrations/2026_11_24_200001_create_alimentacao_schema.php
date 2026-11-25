<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS alimentacao');

        Schema::create('alimentacao.cardapio_dia', function (Blueprint $table): void {
            $table->id();
            $table->date('data');
            $table->string('escola', 160)->default('Rede');
            $table->json('refeicoes');
            $table->timestamps();
            $table->unique(['data', 'escola']);
        });

        Schema::create('alimentacao.restricao_aluno', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('aluno_id');
            $table->string('restricao', 120);
            $table->timestamps();
            $table->index('aluno_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alimentacao.restricao_aluno');
        Schema::dropIfExists('alimentacao.cardapio_dia');
        DB::statement('DROP SCHEMA IF EXISTS alimentacao CASCADE');
    }
};
