# ADR-0017 — Autenticação e papéis para os servidores da secretaria

- **Status:** Proposta
- **Data:** 2026-11-26
- **Fase:** Pós-protótipo

## Contexto

O ADR-0008 deixa a secretaria aberta (demo) ou atrás de um Basic Auth com um único usuário compartilhado. Não há trilha de quem mudou cada status nem separação por secretaria (Obras, Limpeza Urbana etc.).

## Opções em avaliação

- Login próprio do Laravel (Breeze/Fortify) com tabela de servidores e papel por secretaria.
- Supabase Auth, aproveitando o banco já em uso.
- SSO corporativo da prefeitura (OIDC), se existir.

## Quando decidir

Antes de qualquer uso com dados reais ou piloto com servidores. É pré-requisito para remover `SECRETARIA_ABERTA`.

## Relacionadas

Substitui ADR-0008. Afeta ADR-0009 (gravar autor no histórico).
