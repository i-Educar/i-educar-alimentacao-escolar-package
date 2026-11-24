<?php

namespace iEducar\Packages\BuritiAlimentacaoEscolar\Services;

final class CardapioDia
{
    /** @param list<array{refeicao: string, itens: list<string>}> $refeicoes */
    public function __construct(private readonly string $data, private readonly array $refeicoes, private readonly string $escola = '') {}
    public function totalItens(): int { $n=0; foreach($this->refeicoes as $r){$n+=count($r['itens']);} return $n; }
    /** @return array{data:string,escola:string,refeicoes:list<array{refeicao:string,itens:list<string>}>,total_itens:int} */
    public function toArray(): array { return ['data'=>$this->data,'escola'=>$this->escola,'refeicoes'=>$this->refeicoes,'total_itens'=>$this->totalItens()]; }
}
