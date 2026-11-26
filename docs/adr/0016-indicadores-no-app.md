# ADR-0016 — Indicadores e análise publicados dentro do próprio app

- **Status:** Aceita
- **Data:** 2026-11-25 (`/indicadores`), 2026-11-26 (`/analise`)
- **Fase:** Protótipo / 4

## Contexto

O painel Power BI da Fase 3 não foi demonstrado, e transparência para o cidadão
pede uma página pública, sem licença nem login.

## Decisão

- `/indicadores`: resolutividade no prazo, tempo típico (mediana dos resolvidos),
  atrasados e NPA, calculados em tempo real a partir do banco do app.
- `/analise`: versão visual da EDA da Fase 4, com números exportados pelo pipeline
  e link para o notebook no Colab.

## Consequências

- Os indicadores do app usam a amostra (600 chamados); a análise usa a base inteira.
  As páginas deixam claro qual é qual.
- Painéis gerenciais da Fase 5 ainda precisam de decisão (ver ADR-0019).

## Evidência

`app/app/Support/Indicadores.php`, rotas `indicadores` e `analise`, commits `c8bea4a`, `00ffbb4`.
