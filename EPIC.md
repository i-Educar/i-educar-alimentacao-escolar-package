# EPIC — Alimentação Escolar (PNAE)

| Campo | Valor |
| --- | --- |
| Pacote | `buriti/i-educar-alimentacao-escolar-package` |
| TR Laguna | **5.2** |
| Prioridade | **P0** |
| Nota atual (executivo) | **D** |
| Fonte | `packages/buriti/docs/laguna/DOC-EXECUTIVO-PE-77-2026-LAGUNA-IEDUCAR.md` |
| Regra de ouro | Pregão Eletrônico 77/2026 · Laguna/SC · PoC ≥ 85% por módulo · gaps ≤ 30 dias |
| Base atual | Sem módulo dedicado; roadmap interno em packages/buriti/docs/laguna/ROADMAP-TRANSPORTE-E-ALIMENTACAO-ESCOLAR.md |

## Objetivo

Produto greenfield de merenda escolar alinhado ao TR Laguna e à política PNAE, demonstrável na PoC.

## Padrão técnico (i-Educar 2.12)

| Item | Valor |
| --- | --- |
| Alvo | i-Educar **2.12** · PHP **≥ 8.3** · Illuminate **^11|^12|^13** (Laravel 13 no app) |
| Composer | `buriti/i-educar-alimentacao-escolar-package` |
| Namespace | `iEducar\Packages\BuritiAlimentacaoEscolar\` → `src/` |
| Compat | `extra.ieducar.compatible: ["2.12"]` · GPL-2.0-or-later · `type: library` |
| Schema / dados | alimentacao |
| Rotas | prefixo `alimentacao-escolar`; SME com middleware `ieducar.*` + `auth`; API apps via Sanctum quando exposto |
| Tenancy | `PackageTenant::allows('buriti/i-educar-alimentacao-escolar-package')` antes de migrations/menu |
| Plug-and-play | path em `packages/buriti/…`; manter em `ignore` até instalável |
| Referência | `packages/buriti/PADRAO-PACOTE-2.12.md` |

**Nota de arquitetura:** Greenfield PNAE; não misturar com almoxarifado (5.7).

## Cobertura 100% do TR

Este EPIC cobre **todos** os requisitos do módulo(s) 5.2 listados em [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) (55 itens OCR).

- PoC: ≥ **85%** demonstráveis e operacionais.
- Até **15%** restante: produção em ≤ **30 dias**, sem ônus.
- Nenhum item do `REQUISITOS-TR.md` pode ficar fora do `ROADMAP.md` / `TODO.md` (direto ou por grupo rastreável).

## Épicos filhos

1. Cardápio por escola/turno com ficha técnica e composição nutricional (TACO/referências oficiais).
2. Controle de estoque de gêneros, mapas de distribuição, sobras/restos e rendimento.
3. Empenho/verba, chamada pública de agricultores e rastreio de entrega.
4. SISVAN / antropometria vinculada ao aluno do SGE.
5. Expor cardápio via `i-educar-apps-api-package` (consumo pelo app família, UI fora deste monorepo).

## Critérios de aceite (PoC)

- [ ] Checklist [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) com **100%** dos itens classificados (atende / gap / N/A)
- [ ] Itens **atende** demonstráveis na PoC (≥ 85% do módulo 5.2)
- [ ] Itens **gap** ≤ 15% com plano ≤ 30 dias em produção, sem ônus
- [ ] Nenhum requisito do TR omitido da matriz


## Fora de escopo deste EPIC

- Orçamento comercial da proposta Laguna.
- Substituição do core i-Educar fora do domínio do módulo.
