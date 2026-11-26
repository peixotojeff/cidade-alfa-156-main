# ADR-0012 — Avaliação do cidadão em três níveis e NPA

- **Status:** Aceita
- **Data:** 2026-11-25
- **Fase:** Protótipo

## Contexto

Prazo cumprido não garante problema resolvido. Era preciso um sinal de satisfação
simples, sem estrelas (vetadas pelo `DESIGN.md`).

## Decisão

Depois de resolvido, o chamado aceita **uma** avaliação: Excelente, Bom ou Péssimo.
O indicador é o NPA = % Excelente − % Péssimo, geral e por serviço, em `/indicadores`.
Só chamados abertos no app são avaliáveis.

## Consequências

- Quem tem o protocolo avalia; não há como saber se é o autor (ver ADR-0006).
- Rota com `throttle:20,1`.

## Evidência

Migração `2026_11_25_000001_add_avaliacao_to_demandas.php`, `DemandaController::avaliar`, commit `2ede334`.
