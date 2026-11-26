# ADR-0023 — API pública para app móvel ou integração com o 156

- **Status:** Proposta
- **Data:** 2026-11-26
- **Fase:** Pós-protótipo

## Contexto

Na base do SP156, o canal Mobile lidera nos 6 serviços com local (52,5% dos buracos). O protótipo é web responsivo e renderizado no servidor (ADR-0001).

## Opções em avaliação

- Manter web responsivo e instalável (PWA).
- Expor API REST versionada (Laravel Sanctum) para app nativo ou para o sistema 156 da prefeitura.

## Quando decidir

Se a Fase 5 ou um piloto exigir app nativo ou integração com sistema existente.

## Relacionadas

ADR-0001, ADR-0006.
