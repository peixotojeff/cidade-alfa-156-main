# ADR-0021 — Integração dos chamados do app com a plataforma de dados

- **Status:** Proposta
- **Data:** 2026-11-26
- **Fase:** 5

## Contexto

O app grava no Postgres (Supabase); o Databricks só recebeu CSVs sintéticos e as bases da Fase 4. Não há fluxo entre os dois.

## Opções em avaliação

- Exportação agendada (CSV/Parquet) do Postgres para o volume Raw.
- Leitura direta do Postgres pelo Databricks (JDBC) com usuário somente leitura.
- CDC (replicação lógica) — provavelmente excessivo para o volume do projeto.
Em todos os casos, pseudonimizar antes de sair do ambiente local (ADR-0013).

## Quando decidir

Junto com o ADR-0019, se os painéis forem no Databricks/Power BI.

## Relacionadas

ADR-0002, ADR-0013, ADR-0019.
