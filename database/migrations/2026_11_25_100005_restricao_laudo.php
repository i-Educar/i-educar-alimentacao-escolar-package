<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('alimentacao.restricao_aluno')) {
            return;
        }
        DB::statement('ALTER TABLE alimentacao.restricao_aluno ADD COLUMN IF NOT EXISTS laudo_path varchar(255) NULL');
        DB::statement('ALTER TABLE alimentacao.restricao_aluno ADD COLUMN IF NOT EXISTS laudo_nome varchar(180) NULL');
        DB::statement('ALTER TABLE alimentacao.restricao_aluno ADD COLUMN IF NOT EXISTS observacao text NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('alimentacao.restricao_aluno')) {
            return;
        }
        DB::statement('ALTER TABLE alimentacao.restricao_aluno DROP COLUMN IF EXISTS laudo_path');
        DB::statement('ALTER TABLE alimentacao.restricao_aluno DROP COLUMN IF EXISTS laudo_nome');
        DB::statement('ALTER TABLE alimentacao.restricao_aluno DROP COLUMN IF EXISTS observacao');
    }
};
