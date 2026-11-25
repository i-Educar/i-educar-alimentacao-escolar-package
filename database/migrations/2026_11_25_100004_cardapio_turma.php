<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('alimentacao.cardapio_dia')) {
            return;
        }

        DB::statement("ALTER TABLE alimentacao.cardapio_dia ADD COLUMN IF NOT EXISTS turma varchar(160) NULL");
        DB::statement("ALTER TABLE alimentacao.cardapio_dia ADD COLUMN IF NOT EXISTS turma_id integer NULL");
        DB::statement("ALTER TABLE alimentacao.restricao_aluno ADD COLUMN IF NOT EXISTS escola varchar(160) NULL");
        DB::statement("ALTER TABLE alimentacao.restricao_aluno ADD COLUMN IF NOT EXISTS turma_id integer NULL");

        // Amplia unicidade: data + escola + turma (null = cardápio da escola inteira)
        try {
            DB::statement('ALTER TABLE alimentacao.cardapio_dia DROP CONSTRAINT IF EXISTS cardapio_dia_data_escola_unique');
        } catch (\Throwable) {
            //
        }
        try {
            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS cardapio_dia_data_escola_turma_uidx ON alimentacao.cardapio_dia (data, escola, COALESCE(turma, \'\'))');
        } catch (\Throwable) {
            //
        }
    }

    public function down(): void
    {
        //
    }
};
