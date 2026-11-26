# ADR-0018 — Envio real de notificações ao cidadão

- **Status:** Proposta
- **Data:** 2026-11-26
- **Fase:** Pós-protótipo

## Contexto

Hoje o aviso é simulado (ADR-0009). O container é síncrono e sem fila (ADR-0005), e o cidadão é anônimo (ADR-0006), então não há para onde enviar.

## Opções em avaliação

- WhatsApp Business API.
- SMS ou e-mail, com opt-in no momento da abertura.
- Fila durável para o envio: Postgres (`jobs` do Laravel), Vercel Queues ou serviço externo.

## Quando decidir

Depois do ADR-0024 (como obter contato do cidadão com consentimento).

## Relacionadas

ADR-0005, ADR-0009, ADR-0024.
