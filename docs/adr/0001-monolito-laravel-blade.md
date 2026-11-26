# ADR-0001 — Protótipo como monólito Laravel 12 com Blade

- **Status:** Aceita
- **Data:** 2026-11-23
- **Fase:** Protótipo

## Contexto

A equipe precisava de um protótipo funcional do fluxo do cidadão (abrir, acompanhar)
e da secretaria, simples de demonstrar e de manter por uma equipe pequena.

## Decisão

Um único app Laravel 12 em `app/`, com páginas renderizadas no servidor (Blade),
um controlador (`DemandaController`) e uma página por objetivo do cidadão. JavaScript
só onde é indispensável (mapa Leaflet e geolocalização).

## Alternativas consideradas

- SPA (React/Vue) + API — dobraria a superfície (duas bases, CORS, autenticação de API) sem ganho para a demonstração.
- No-code (Power Apps, AppSheet) — não permitiria controlar SLA, histórico e testes.

## Consequências

- Formulários funcionam sem JS; CSRF e validação vêm do framework.
- Testes de feature cobrem o fluxo inteiro (`app/tests/Feature/DemandaTest.php`).
- Um app mobile nativo, se vier, exigirá expor uma API (ver ADR-0023).

## Evidência

`app/routes/web.php`, commit `8b3092e`, `docs/superpowers/specs/2026-11-23-prototipo-design.md`.
