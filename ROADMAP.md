# ROADMAP — Alimentação Escolar (PNAE)

> Pacote: `buriti/i-educar-alimentacao-escolar-package` · TR **5.2** · Prioridade **P0** · Nota **D**  
> Alinhado a `packages/buriti/docs/laguna/DOC-EXECUTIVO-PE-77-2026-LAGUNA-IEDUCAR.md`

## Visão

Produto greenfield de merenda escolar alinhado ao TR Laguna e à política PNAE, demonstrável na PoC.

## Fases

### F0 · Descoberta

Matriz item a item 5.2.x OCR × telas; modelo de dados aluno/escola/turno; escopo MVP PoC.

### F1 · MVP PoC

Cardápio + ficha técnica + estoque básico + mapa de distribuição + lançamento de refeição/sobras.

### F2 · Financeiro/PNAE

Empenhos, chamada pública, prestação mínima auditável.

### F3 · SISVAN + app

Antropometria e cardápio expostos na apps-api; relatórios CAE.

### F4 · 30 dias pós-PoC

Fechar até 15% faltantes sem ônus.

## Cobertura 100% dos requisitos (TR)

Fonte: [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) — **55 itens**.

Cada lote deve sair da PoC como **atende** ou entrar no plano de 30 dias.

### Lote R1 — itens 5.2.1 … 5.2.16 (15)

- `5.2.1`, `5.2.2`, `5.2.3`, `5.2.4`, `5.2.5`, `5.2.6`, `5.2.7`, `5.2.8`, `5.2.9`, `5.2.10`, `5.2.12`, `5.2.13`, `5.2.14`, `5.2.15`, `5.2.16`

- [ ] Implementar / validar / evidenciar na matriz PoC

### Lote R2 — itens 5.2.17 … 5.2.32 (15)

- `5.2.17`, `5.2.18`, `5.2.20`, `5.2.21`, `5.2.22`, `5.2.23`, `5.2.24`, `5.2.25`, `5.2.26`, `5.2.27`, `5.2.28`, `5.2.29`, `5.2.30`, `5.2.31`, `5.2.32`

- [ ] Implementar / validar / evidenciar na matriz PoC

### Lote R3 — itens 5.2.33 … 5.2.47 (15)

- `5.2.33`, `5.2.34`, `5.2.35`, `5.2.36`, `5.2.37`, `5.2.38`, `5.2.39`, `5.2.40`, `5.2.41`, `5.2.42`, `5.2.43`, `5.2.44`, `5.2.45`, `5.2.46`, `5.2.47`

- [ ] Implementar / validar / evidenciar na matriz PoC

### Lote R4 — itens 5.2.48 … 5.2.51.3 (10)

- `5.2.48`, `5.2.49`, `5.2.50`, `5.2.50.1`, `5.2.50.2`, `5.2.50.3`, `5.2.51`, `5.2.51.1`, `5.2.51.2`, `5.2.51.3`

- [ ] Implementar / validar / evidenciar na matriz PoC

### Gate 100%

- [ ] Todos os lotes R* com status atende ou gap≤30dias
- [ ] Soma atende ≥ 85% na demonstração

## Conformidade Laravel / pacote i-Educar 2.12

Antes da fase de código:

1. Criar `composer.json` com `php >=8.3`, `illuminate/support`, provider em `extra.laravel`, `compatible: ["2.12"]`.
2. Scaffold: `src/Providers`, `Http/Controllers`, `Services`, `Support`, `database/migrations`, `routes/web.php`, views Blade.
3. Schema `alimentacao` (ou estratégia documentada); FKs lógicas para núcleo (`ref_cod_aluno`, `ref_cod_escola`, etc.).
4. Menu/processos em migration própria; respeitar multi-tenant.
5. Não acoplar UI mobile; dados para lojas só via ``buriti/i-educar-apps-api-package``.
6. Seguir `packages/buriti/PADRAO-PACOTE-2.12.md`.

## Dependências

- Cadastro mestre de aluno/escola/turma no i-Educar (SGE).
- Matriz item a item OCR do Anexo I antes de cravar %.
- Alinhamento com `i-educar-apps-api-package` (APIs 5.13/5.14; UI/lojas em equipe externa) e exports (4.x) quando o módulo expõe dados ao cidadão/SME.

## Riscos

- Valor global do edital incompatível com greenfield total — reuso e MVP demonstrável são obrigatórios.
- PDF escaneado: numerações OCR podem exigir reconciliação manual.
- PoC só conta o que estiver operacional no dia.

## Definição de pronto da fase PoC

Checklist do `TODO.md` marcado para a fatia MVP + roteiro de demonstração ensaiado.
