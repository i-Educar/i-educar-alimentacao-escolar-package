# Manual de funções — Alimentação escolar (Laguna)

Cor-base `#0f6b4c` · ícone `fa-cutlery` · processos `8811xx` · menu `order` 9 (após transporte).

## Telas

| Função | URL | O que faz |
| --- | --- | --- |
| Painel | `/alimentacao-escolar` | Contadores + atalhos; alerta se schema ausente |
| Cardápio | `/alimentacao-escolar/cardapio` | Refeições do dia; conflitos com restrições |
| Restrições | `/alimentacao-escolar/restricoes` | Lista `ref_cod_aluno` + restrição |
| Mapa | `/alimentacao-escolar/mapa` | Porções por escola/refeição |
| Demo | `/alimentacao-escolar/demo` | Executa seed PoC |

## CLI

```bash
php artisan alimentacao:demo --data=YYYY-MM-DD --escola=Rede
php tests/run.php   # na pasta do pacote
```

## Schema (padrão 2.12)

- `alimentacao.cardapio_dia` — data, escola, refeições (jsonb)
- `alimentacao.restricao_aluno` — `ref_cod_aluno`, restrição

Não duplica aluno do núcleo.

## Primeiro teste

1. Liberar pacote em `tenants.local.php`  
2. `php artisan migrate`  
3. Login SME → menu Alimentação → Demo → Cardápio  
4. Conferir conflito “leite” e cor verde no layout  

Roteiro completo do programa: `packages/buriti/docs/MANUAL-INTEGRACAO-LAGUNA.md` (monorepo).
