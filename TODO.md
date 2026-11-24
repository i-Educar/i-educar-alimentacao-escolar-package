# TODO — Alimentação Escolar (PNAE)

> Pacote: `buriti/i-educar-alimentacao-escolar-package` · TR **5.2** · Prioridade **P0**

## Backlog Laguna

- [ ] Extrair checklist 5.2.x do OCR do Anexo I
- [ ] Definir schema transporte_escolar-like: alimentacao.*
- [ ] CRUD cardápio / refeição / porção
- [ ] Ficha técnica + vínculo TACO
- [ ] Estoque por unidade + movimentação
- [ ] Mapa diário de distribuição escola
- [ ] Registro de sobras/restos
- [ ] Empenho e fonte de recurso
- [ ] Chamada pública (MVP)
- [ ] Antropometria / SISVAN
- [ ] API cardápio via i-educar-apps-api-package
- [ ] Roteiro de demo PoC ≥ 85%

## Scaffold 2.12 (quando iniciar código)

- [ ] `composer.json` (`buriti/…`, PHP ≥8.3, illuminate/support, `compatible: ["2.12"]`)
- [ ] `ServiceProvider` + `const PACKAGE`
- [ ] `routes/web.php` com middleware i-Educar
- [ ] Migrations no schema do domínio + guard `PackageTenant`
- [ ] Processos/menu (faixa própria)
- [ ] README + INSTALL espelhando transporte/setup
- [ ] Entrada futura em `packages/plug-and-play.json` (fora do ignore)
- [ ] Revisar `packages/buriti/PADRAO-PACOTE-2.12.md`

## Cobertura 100% do TR

- [ ] Percorrer 100% de [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) (55 itens) e marcar atende/gap/N/A

- [ ] Abrir tarefas para **cada gap** (ou agrupar com IDs explícitos)

- [ ] Evidências de PoC por item atende

- [ ] Lista dos ≤15% com datas ≤30 dias

### Lotes (mesmo do ROADMAP)

- [ ] Lote R1: 5.2.1 … 5.2.16

- [ ] Lote R2: 5.2.17 … 5.2.32

- [ ] Lote R3: 5.2.33 … 5.2.47

- [ ] Lote R4: 5.2.48 … 5.2.51.3

## Gate PoC

- [ ] `REQUISITOS-TR.md` 100% classificado
- [ ] ≥ 85% atende com evidência
- [ ] ≤ 15% gap com plano ≤ 30 dias
- [ ] Roteiro binário ensaiado

## Referências

- `tmp/DOC-EXECUTIVO-PE-77-2026-LAGUNA-IEDUCAR.md`
- OCR: `/tmp/edital-ocr/p-01.txt` … `p-79.txt` (quando disponível)
