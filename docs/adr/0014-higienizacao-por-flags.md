# ADR-0014 — Higienização por flags, não por exclusão; métricas robustas

- **Status:** Aceita
- **Data:** 2026-11-26
- **Fase:** 4

## Contexto

A base do SP156 (1,5 milhão de chamados) tem coordenadas inválidas, nomes trocados,
31% de linhas idênticas não deduplicáveis e tempos com cauda longa. Apagar outliers
esconderia justamente os atrasos que o projeto quer mostrar.

## Decisão

- Nenhum chamado é apagado sem motivo; cada problema vira uma flag
  (`flag_coord_valida`, `flag_outlier_tempo`, `flag_backlog_critico`, duplicata provável…)
  e cada métrica escolhe o que excluir. Decisões registradas em `limpeza_decisoes.csv`.
- Métricas gerenciais usam **mediana**, percentis e média **winsorizada no p99**;
  outlier = Tukey (> Q3 + 1,5·IQR) por serviço.
- Saídas em três bases Parquet no padrão medalhão: bruto (Raw), limpo (Prata),
  atributos de SLA dos 6 serviços (Ouro). Só tabelas, figuras e resumo são versionados.

## Consequências

- Reprodutível a partir das planilhas públicas (`pipeline_fase4.py`, notebook Colab).
- Duplicatas prováveis ficam fora das métricas de SLA, mas dentro dos volumes.

## Evidência

`fase4/README.md` §1–3, `fase4/pipeline_fase4.py`, `fase4/saida/tabelas/limpeza_decisoes.csv`, commit `00ffbb4`.
