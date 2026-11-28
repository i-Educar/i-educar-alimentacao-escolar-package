<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Gestão avançada: refeições por item, multi-segmento em porções,
 * responsável técnico e configurações do módulo.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE SCHEMA IF NOT EXISTS alimentacao');

        if (Schema::hasTable('alimentacao.item')) {
            DB::statement("ALTER TABLE alimentacao.item ADD COLUMN IF NOT EXISTS refeicoes_json text NULL");
        }

        if (Schema::hasTable('alimentacao.perfil_servico')) {
            DB::statement("ALTER TABLE alimentacao.perfil_servico ADD COLUMN IF NOT EXISTS segmentos_json text NULL");
            DB::statement("ALTER TABLE alimentacao.perfil_servico ADD COLUMN IF NOT EXISTS estimado boolean NOT NULL DEFAULT false");
            DB::statement("ALTER TABLE alimentacao.perfil_servico ADD COLUMN IF NOT EXISTS origem varchar(40) NULL");
        }

        if (! Schema::hasTable('alimentacao.configuracao')) {
            Schema::create('alimentacao.configuracao', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('chave', 80)->unique();
                $table->text('valor')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('alimentacao.responsavel_tecnico')) {
            Schema::create('alimentacao.responsavel_tecnico', function (Blueprint $table): void {
                $table->bigIncrements('id');
                $table->string('nome', 160);
                $table->string('registro', 80)->nullable()->comment('CRN / registro profissional');
                $table->string('cargo', 120)->nullable();
                $table->string('email', 160)->nullable();
                $table->boolean('ativo')->default(true);
                $table->boolean('padrao')->default(false);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('alimentacao.responsavel_tecnico');
        Schema::dropIfExists('alimentacao.configuracao');
    }
};
