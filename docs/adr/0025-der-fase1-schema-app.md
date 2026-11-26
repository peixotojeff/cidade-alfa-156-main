# ADR-0025 — Alinhar o DER da Fase 1 com o esquema do app

- **Status:** Proposta
- **Data:** 2026-11-26
- **Fase:** Transversal

## Contexto

O DER da Fase 1 (Oracle Data Modeler, prefixo `T_DD_`) e o esquema do app (`demandas`, `historico_status`) evoluíram separados. Campos como `origem`, `avaliacao` e `finalizada_em` só existem no app; apoios e cidadão só no DER.

## Opções em avaliação

- Atualizar o DER para refletir o app e marcar o que é futuro.
- Renomear tabelas do app para o padrão do DER (custo alto, pouco ganho).
- Manter os dois e publicar um mapa de correspondência.

## Quando decidir

Antes da entrega final, para a banca não ver dois modelos contraditórios.

## Relacionadas

ADR-0002, ADR-0020, ADR-0024.
