# ADR-0005 — Container sem estado: sessão em cookie, cache em memória, fila síncrona

- **Status:** Aceita
- **Data:** 2026-11-25
- **Fase:** Protótipo

## Contexto

Instâncias do container no Vercel nascem e morrem a qualquer momento e não
compartilham disco.

## Decisão

`SESSION_DRIVER=cookie` (criptografado com `APP_KEY`, `SESSION_SECURE_COOKIE=true`),
`CACHE_STORE=array`, `QUEUE_CONNECTION=sync` e logs em `stderr`.

## Consequências

- `APP_KEY` precisa ser fixa entre deploys, senão as sessões se invalidam.
- Não há trabalho assíncrono: notificações reais exigirão fila durável (ver ADR-0018).
- Cache não sobrevive entre requisições; indicadores são recalculados a cada acesso.

## Evidência

`Dockerfile` (bloco `ENV`), README › tabela de variáveis.
