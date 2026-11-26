# ADR-0019 — Ferramenta dos painéis analíticos da Fase 5

- **Status:** Proposta
- **Data:** 2026-11-26
- **Fase:** 5

## Contexto

A Fase 3 previu Power BI sobre a camada Ouro; a Fase 4 entregou a base `sp156_sla_atributos` pronta e já publica `/analise` no app (ADR-0016).

## Opções em avaliação

- Power BI sobre tabelas Delta no Databricks (desenho original).
- Painéis do Databricks SQL (sem licença extra).
- Estender as páginas do app com mapa de calor por subprefeitura, funil de faixas e tendência semanal.

## Quando decidir

No início da Fase 5. Incluir a normalização por população (limitação registrada na Fase 4).

## Relacionadas

ADR-0013, ADR-0014, ADR-0016.
