#!/usr/bin/env php
<?php
declare(strict_types=1);

require __DIR__.'/../src/Services/CardapioDia.php';
require __DIR__.'/../src/Services/RestricaoAlimentar.php';
require __DIR__.'/../src/Services/MapaDistribuicao.php';

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

echo "\nResultado: $ok ok, $fail falhas\n";
exit($fail>0?1:0);
