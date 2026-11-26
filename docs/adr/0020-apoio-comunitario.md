# ADR-0020 — Apoio comunitário real (RN-03) e sugestão de chamado existente

- **Status:** Proposta
- **Data:** 2026-11-26
- **Fase:** Pós-protótipo

## Contexto

A Fase 4 mostrou 14,2% de reclamações repetidas em veículo abandonado e 6,3% em buraco. O score atual usa repetições como proxy (ADR-0015). O modelo da Fase 1 prevê `T_DD_APOIO_DEMANDA`.

## Opções em avaliação

- Ao abrir chamado, buscar chamados do mesmo serviço num raio de N metros e oferecer "apoiar este" em vez de abrir outro.
- Tabela de apoios com limite por dispositivo/protocolo (sem identidade forte) ou exigir login (ADR-0024).

## Quando decidir

Quando o app tiver uso real o bastante para gerar apoios. Antes, investigar a coordenada padrão de geocodificação apontada no ADR-0015.

## Relacionadas

Substitui ADR-0015 (entrada do score). Depende de ADR-0024.
