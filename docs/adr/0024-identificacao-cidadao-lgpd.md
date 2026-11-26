# ADR-0024 — Identificação do cidadão, consentimento e retenção (LGPD)

- **Status:** Proposta
- **Data:** 2026-11-26
- **Fase:** Pós-protótipo

## Contexto

O protótipo não coleta dado pessoal (ADR-0006). Notificações reais, "meus chamados" e antiabuso nos apoios dependem de algum contato ou identidade. A Fase 3 tem `tb_cadastro_cidadao` sintética.

## Opções em avaliação

- Continuar anônimo e pedir só um contato opcional com consentimento explícito.
- Login gov.br (identidade forte, sem guardar CPF no app).
- Definir base legal, prazo de retenção e anonimização de chamados antigos.

## Quando decidir

Antes de ADR-0018 e ADR-0020.

## Relacionadas

ADR-0006, ADR-0013, ADR-0018, ADR-0020.
