#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__.'/../src/Services/CardapioDia.php';
require __DIR__.'/../src/Services/RestricaoAlimentar.php';
require __DIR__.'/../src/Services/MapaDistribuicao.php';
require __DIR__.'/../src/Services/EscopoCardapio.php';

$ok=0; $fail=0;
$assert = static function(bool $c, string $m) use (&$ok,&$fail): void {
  echo ($c? '  OK  ':' FAIL ').$m."\n";
  $c ? $ok++ : $fail++;
};

$c = new \iEducar\Packages\BuritiAlimentacaoEscolar\Services\CardapioDia('2026-03-01', [['refeicao'=>'almoco','itens'=>['arroz','feijao']]], 'EMEF');
$assert($c->totalItens()===2, 'cardápio conta itens');
$r = new \iEducar\Packages\BuritiAlimentacaoEscolar\Services\RestricaoAlimentar(1, ['leite']);
$assert($r->conflita(['pão','leite'])===true, 'restrição detecta conflito');
$assert($r->conflita(['arroz'])===false, 'restrição sem conflito');
$m = \iEducar\Packages\BuritiAlimentacaoEscolar\Services\MapaDistribuicao::consolidar([['escola'=>'A','refeicao'=>'almoco','quantidade'=>10],['escola'=>'A','refeicao'=>'almoco','quantidade'=>5]]);
$assert($m['total']===15, 'mapa soma quantidades');

$Escopo = \iEducar\Packages\BuritiAlimentacaoEscolar\Services\EscopoCardapio::class;
$assert($Escopo::normalizar('SEGMENTO') === 'segmento', 'escopo normaliza');
$assert($Escopo::validar('turma', 'EMEF', '')['ok'] === false, 'turma exige nome');
$assert($Escopo::validar('segmento', 'EMEF', '', 'infantil')['ok'] === true, 'segmento infantil ok');
$assert($Escopo::validar('rede', 'X', '')['escola'] === 'Rede', 'rede força escola Rede');
$assert($Escopo::rotuloSegmento('medio') === 'Ensino Médio', 'rótulo segmento');

$multiTurmas = $Escopo::expandirDestinos('turma', 'EMEF', [
    ['nome' => '1º A', 'id' => 1],
    ['nome' => '2º B', 'id' => 2],
], []);
$assert(($multiTurmas['ok'] ?? false) && count($multiTurmas['destinos'] ?? []) === 2, 'multi turmas expande 2 destinos');
$multiSeg = $Escopo::expandirDestinos('segmento', 'EMEF', [], ['infantil', 'medio']);
$assert(($multiSeg['ok'] ?? false) && count($multiSeg['destinos'] ?? []) === 2, 'multi segmentos expande 2 destinos');
$escolaTurmaPrioridade = $Escopo::expandirDestinos('escola', 'EMEF', [['nome' => '1º A', 'id' => 1]], ['infantil']);
$assert(($escolaTurmaPrioridade['destinos'][0]['turma'] ?? '') === '1º A', 'escola: turmas prevalecem sobre segmentos');

$rede = $Escopo::expandirDestinos('rede', 'qualquer', [], []);
$assert(($rede['ok'] ?? false) && ($rede['destinos'][0]['escola'] ?? '') === 'Rede', 'rede → destino Rede');
$escolaSo = $Escopo::expandirDestinos('escola', 'EMEF Centro', [], []);
$assert(($escolaSo['ok'] ?? false) && count($escolaSo['destinos'] ?? []) === 1 && ($escolaSo['destinos'][0]['turma'] ?? 'x') === '', 'escola sem turma/segmento = 1 destino');
$escolaSeg = $Escopo::expandirDestinos('escola', 'EMEF', [], ['eja', 'aee']);
$assert(($escolaSeg['ok'] ?? false) && count($escolaSeg['destinos'] ?? []) === 2, 'escola com segmentos expande');
$turmaVazia = $Escopo::expandirDestinos('turma', 'EMEF', [], []);
$assert(($turmaVazia['ok'] ?? true) === false, 'turma sem seleção falha');
$segVazio = $Escopo::expandirDestinos('segmento', 'EMEF', [], []);
$assert(($segVazio['ok'] ?? true) === false, 'segmento sem seleção falha');
$escolaSemNome = $Escopo::expandirDestinos('escola', 'Rede', [], []);
$assert(($escolaSemNome['ok'] ?? true) === false, 'escola=Rede no escopo escola falha');

$turmasNorm = $Escopo::normalizarTurmas(['10::1º A', '2º B'], '3º C', 3);
$assert(count($turmasNorm) === 3, 'normaliza turmas id::nome + legado');
$segsNorm = $Escopo::normalizarSegmentos(['infantil', 'xyz', 'medio'], 'eja');
$assert($segsNorm === ['infantil', 'medio', 'eja'] || (count($segsNorm) === 3 && in_array('eja', $segsNorm, true)), 'normaliza segmentos válidos + legado');
$assert(count($Escopo::segmentos()) >= 6, 'catálogo de segmentos completo');
$assert(count($Escopo::opcoes()) === 4, '4 opções de escopo');

echo "\nResultado: $ok ok, $fail falhas\n";
exit($fail>0?1:0);
