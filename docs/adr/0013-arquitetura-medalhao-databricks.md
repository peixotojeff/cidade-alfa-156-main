# ADR-0013 — Dados sensíveis locais e medalhão pseudonimizado no Databricks

- **Status:** Aceita (implementação parcial)
- **Data:** Fase 3 (anterior a 2026-11-23)
- **Fase:** 3

## Contexto

A plataforma analítica precisa cruzar chamados e cidadãos sem expor dados pessoais
a um ambiente de nuvem compartilhado.

## Decisão

Dados sensíveis ficam no ambiente local; apenas dados pseudonimizados seguem para o
Databricks, organizados em camadas Raw → Bronze → Prata → Ouro no catálogo
`cidadania_digital_cidade_alfa`, com consumo previsto pelo Power BI.

## Consequências

- Demonstrado: carga Raw (5 CSVs sintéticos) e CRUD na tabela Bronze `tb_cadastro_cidadao`.
- Não demonstrado: Prata e Ouro populadas e o painel Power BI.
- A Fase 4 gera bases Raw/Prata/Ouro em Parquet prontas para virar Delta (ADR-0014).
- Ainda não há caminho do app para o Databricks (ver ADR-0021).

## Evidência

README › Fase 3 (PDFs `Data_Drivens_PBL_Fase3_.zip`).
