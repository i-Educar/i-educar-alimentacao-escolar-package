<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

final class EscopoCardapio
{
    public const REDE = 'rede';

    public const ESCOLA = 'escola';

    public const TURMA = 'turma';

    public const SEGMENTO = 'segmento';

    /** @return array<string, string> */
    public static function opcoes(): array
    {
        return [
            self::REDE => 'Rede (todas as escolas)',
            self::ESCOLA => 'Escola',
            self::TURMA => 'Turma',
            self::SEGMENTO => 'Segmento de ensino',
        ];
    }

    /** @return array<string, string> */
    public static function segmentos(): array
    {
        return [
            'infantil' => 'Educação Infantil',
            'fundamental_1' => 'Fundamental I (1º–5º)',
            'fundamental_2' => 'Fundamental II (6º–9º)',
            'medio' => 'Ensino Médio',
            'eja' => 'EJA',
            'aee' => 'AEE / especializado',
        ];
    }

    public static function rotuloSegmento(?string $codigo): string
    {
        $codigo = trim((string) $codigo);
        if ($codigo === '') {
            return '—';
        }

        return self::segmentos()[$codigo] ?? $codigo;
    }

    public static function normalizar(string $escopo): string
    {
        $escopo = mb_strtolower(trim($escopo));

        return array_key_exists($escopo, self::opcoes()) ? $escopo : self::ESCOLA;
    }

    /**
     * Valida combinação escopo + escola/turma/segmento.
     *
     * @return array{ok:bool,erro:?string,escola:string,turma:string,segmento:?string,escopo:string}
     */
    public static function validar(string $escopo, string $escola, string $turma = '', ?string $segmento = null): array
    {
        $escopo = self::normalizar($escopo);
        $escola = trim($escola) ?: 'Rede';
        $turma = trim($turma);
        $segmento = $segmento !== null ? trim($segmento) : null;
        if ($segmento === '') {
            $segmento = null;
        }

        if ($escopo === self::REDE) {
            return ['ok' => true, 'erro' => null, 'escola' => 'Rede', 'turma' => '', 'segmento' => null, 'escopo' => $escopo];
        }
        if ($escopo === self::ESCOLA && ($escola === '' || $escola === 'Rede')) {
            return ['ok' => false, 'erro' => 'Selecione a escola do cardápio.', 'escola' => $escola, 'turma' => '', 'segmento' => null, 'escopo' => $escopo];
        }
        if ($escopo === self::TURMA && $turma === '') {
            return ['ok' => false, 'erro' => 'Informe a turma para cardápio por turma.', 'escola' => $escola, 'turma' => $turma, 'segmento' => $segmento, 'escopo' => $escopo];
        }
        if ($escopo === self::SEGMENTO && ($segmento === null || ! array_key_exists($segmento, self::segmentos()))) {
            return ['ok' => false, 'erro' => 'Selecione o segmento de ensino.', 'escola' => $escola, 'turma' => '', 'segmento' => $segmento, 'escopo' => $escopo];
        }

        return ['ok' => true, 'erro' => null, 'escola' => $escola, 'turma' => $turma, 'segmento' => $segmento, 'escopo' => $escopo];
    }

    /**
     * Normaliza turmas do request: turmas[] com "id::nome" ou nome puro; legado turma + turma_id.
     *
     * @return list<array{nome:string,id:int}>
     */
    public static function normalizarTurmas(mixed $turmas, mixed $turmaLegado = '', mixed $turmaIdLegado = 0): array
    {
        $out = [];
        $lista = is_array($turmas) ? $turmas : [];
        foreach ($lista as $raw) {
            $raw = trim((string) $raw);
            if ($raw === '') {
                continue;
            }
            $id = 0;
            $nome = $raw;
            if (str_contains($raw, '::')) {
                [$idPart, $nomePart] = explode('::', $raw, 2);
                $id = (int) $idPart;
                $nome = trim($nomePart);
            }
            if ($nome === '') {
                continue;
            }
            $chave = mb_strtolower($nome);
            $out[$chave] = ['nome' => $nome, 'id' => $id];
        }

        $legado = trim((string) $turmaLegado);
        if ($legado !== '') {
            $chave = mb_strtolower($legado);
            if (! isset($out[$chave])) {
                $out[$chave] = ['nome' => $legado, 'id' => (int) $turmaIdLegado];
            } elseif ($out[$chave]['id'] < 1 && (int) $turmaIdLegado > 0) {
                $out[$chave]['id'] = (int) $turmaIdLegado;
            }
        }

        return array_values($out);
    }

    /**
     * @return list<string>
     */
    public static function normalizarSegmentos(mixed $segmentos, mixed $segmentoLegado = null): array
    {
        $lista = is_array($segmentos) ? $segmentos : [];
        $out = [];
        foreach ($lista as $raw) {
            $cod = trim((string) $raw);
            if ($cod !== '' && array_key_exists($cod, self::segmentos())) {
                $out[$cod] = $cod;
            }
        }
        $legado = $segmentoLegado !== null ? trim((string) $segmentoLegado) : '';
        if ($legado !== '' && array_key_exists($legado, self::segmentos())) {
            $out[$legado] = $legado;
        }

        return array_values($out);
    }

    /**
     * Expande destino(s) a gravar a partir do escopo e seleções múltiplas.
     * Turmas têm prioridade sobre segmentos quando ambos vêm preenchidos no escopo escola.
     *
     * @param  list<array{nome:string,id:int}>  $turmas
     * @param  list<string>  $segmentos
     * @return array{ok:bool,erro:?string,escopo:string,escola:string,destinos:list<array{escola:string,turma:string,turma_id:int,segmento:?string}>}
     */
    public static function expandirDestinos(string $escopo, string $escola, array $turmas, array $segmentos): array
    {
        $escopo = self::normalizar($escopo);
        $escola = trim($escola) ?: 'Rede';
        $turmas = array_values(array_filter($turmas, static fn (array $t): bool => trim($t['nome'] ?? '') !== ''));
        $segmentos = array_values(array_filter($segmentos, static fn (string $s): bool => array_key_exists($s, self::segmentos())));

        if ($escopo === self::REDE) {
            return [
                'ok' => true,
                'erro' => null,
                'escopo' => $escopo,
                'escola' => 'Rede',
                'destinos' => [['escola' => 'Rede', 'turma' => '', 'turma_id' => 0, 'segmento' => null]],
            ];
        }

        if ($escola === '' || $escola === 'Rede') {
            return [
                'ok' => false,
                'erro' => 'Selecione a escola do cardápio.',
                'escopo' => $escopo,
                'escola' => $escola,
                'destinos' => [],
            ];
        }

        if ($escopo === self::TURMA) {
            if ($turmas === []) {
                return [
                    'ok' => false,
                    'erro' => 'Selecione ao menos uma turma.',
                    'escopo' => $escopo,
                    'escola' => $escola,
                    'destinos' => [],
                ];
            }
            $destinos = [];
            foreach ($turmas as $t) {
                $destinos[] = [
                    'escola' => $escola,
                    'turma' => $t['nome'],
                    'turma_id' => (int) ($t['id'] ?? 0),
                    'segmento' => null,
                ];
            }

            return ['ok' => true, 'erro' => null, 'escopo' => $escopo, 'escola' => $escola, 'destinos' => $destinos];
        }

        if ($escopo === self::SEGMENTO) {
            if ($segmentos === []) {
                return [
                    'ok' => false,
                    'erro' => 'Selecione ao menos um segmento de ensino.',
                    'escopo' => $escopo,
                    'escola' => $escola,
                    'destinos' => [],
                ];
            }
            $destinos = [];
            foreach ($segmentos as $seg) {
                $destinos[] = [
                    'escola' => $escola,
                    'turma' => '',
                    'turma_id' => 0,
                    'segmento' => $seg,
                ];
            }

            return ['ok' => true, 'erro' => null, 'escopo' => $escopo, 'escola' => $escola, 'destinos' => $destinos];
        }

        // escola: turmas OU segmentos OU escola inteira
        if ($turmas !== []) {
            $destinos = [];
            foreach ($turmas as $t) {
                $destinos[] = [
                    'escola' => $escola,
                    'turma' => $t['nome'],
                    'turma_id' => (int) ($t['id'] ?? 0),
                    'segmento' => null,
                ];
            }

            return ['ok' => true, 'erro' => null, 'escopo' => $escopo, 'escola' => $escola, 'destinos' => $destinos];
        }

        if ($segmentos !== []) {
            $destinos = [];
            foreach ($segmentos as $seg) {
                $destinos[] = [
                    'escola' => $escola,
                    'turma' => '',
                    'turma_id' => 0,
                    'segmento' => $seg,
                ];
            }

            return ['ok' => true, 'erro' => null, 'escopo' => $escopo, 'escola' => $escola, 'destinos' => $destinos];
        }

        return [
            'ok' => true,
            'erro' => null,
            'escopo' => $escopo,
            'escola' => $escola,
            'destinos' => [['escola' => $escola, 'turma' => '', 'turma_id' => 0, 'segmento' => null]],
        ];
    }
}
