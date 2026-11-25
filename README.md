# Alimentação escolar (TR 5.2)

Pacote Buriti para o i-Educar **2.12** · Composer `buriti/i-educar-alimentacao-escolar-package`.

## Identidade

| Token | Valor |
| --- | --- |
| Cor base | `#0f6b4c` (verde PNAE) |
| Destaque | `#2d8a5e` |
| Ícone | `fa-cutlery` |
| Processos | `8811xx` |
| Menu raiz `order` | `9` (após transporte `8`) |

UI espelha o transporte: lateral por grupos, botões `--cor-tela`, Font Awesome.

## Rotas SME

| URL | Função |
| --- | --- |
| `/alimentacao-escolar` | Painel |
| `/alimentacao-escolar/cardapio` | Cardápio do dia + conflitos |
| `/alimentacao-escolar/restricoes` | Restrições (`ref_cod_aluno`) |
| `/alimentacao-escolar/mapa` | Mapa de distribuição |
| `/alimentacao-escolar/demo` | Seed PoC |

## Schema

- `alimentacao.cardapio_dia`
- `alimentacao.restricao_aluno`

Migrations só carregam se `PackageTenant::allows('buriti/i-educar-alimentacao-escolar-package')`.

## Comandos

```bash
php artisan alimentacao:demo --data=2026-11-24 --escola=Rede
php packages/buriti/i-educar-alimentacao-escolar-package/tests/run.php
```

## Docs

- `EPIC.md` · `ROADMAP.md` · `TODO.md` · `REQUISITOS-TR.md`
- Manual de integração (monorepo): `packages/buriti/docs/MANUAL-INTEGRACAO-LAGUNA.md`
- UI módulos: `packages/buriti/docs/UI-MODULOS-LAGUNA.md`
- Padrão: `packages/buriti/PADRAO-PACOTE-2.12.md`
