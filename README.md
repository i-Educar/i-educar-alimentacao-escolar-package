# Alimentação escolar

Gestão da merenda escolar municipal no i-Educar: cardápio, restrições alimentares, distribuição e balanço operacional alinhados ao PNAE.

Composer: `buriti/i-educar-alimentacao-escolar-package`

## Para que serve

Oferece à SME e às escolas um módulo único para planejar, executar e acompanhar a alimentação escolar — do cardápio diário às restrições por aluno — com visão de rede e evidências para auditoria e prestação de contas.

## Desenho de funções

| Função | Entrega |
| --- | --- |
| Cardápio do dia / lote | Planejamento de refeições por escola, turno e período; impressão e cronograma |
| Itens e perfis nutricionais | Cadastro de alimentos/preparações e perfis usados no cardápio |
| Restrições alimentares | Registro por aluno (alergias/intolerâncias) com laudo anexável |
| Mapa de distribuição | Visão de quem recebe o quê na rede |
| Balanço e sobras | Acompanhamento de consumo, sobras e ajustes operacionais |
| Histórico e configurações | Trilha do que foi servido e parâmetros do módulo |
| Painel SME | Entrada `/alimentacao-escolar` com visão consolidada |

## Quem usa

- Nutricionistas e equipe de alimentação (SME)
- Direção / merendeiras na escola
- Famílias (consulta via apps, quando habilitado)

## Integrações

- Núcleo i-Educar (aluno, escola, turma)
- Exportação unificada (PDF/relatórios)
- Apps API (cardápio para responsáveis)

## Compatibilidade

| Produto | Versão |
| --- | --- |
| **i-Educar** | **2.12.x** |
| **i-Diário** | Portabilis i-Diário (API / SSO do núcleo) |
| Pacote | `0.2.1` |

Declarado em `composer.json` → `extra.ieducar` / `extra.idiario`.

## Documentação

| Documento | Uso |
| --- | --- |
| [`EPIC.md`](./EPIC.md) | Escopo de produto, aceite e critérios |
| [`ROADMAP.md`](./ROADMAP.md) | Fases e entregas |
| [`TODO.md`](./TODO.md) | Backlog operacional |
| [`REQUISITOS-TR.md`](./REQUISITOS-TR.md) | Rastreio de requisitos (planejamento) |

---

Licença: **GPL-2.0-or-later** · Organização [i-Educar](https://github.com/i-Educar)
