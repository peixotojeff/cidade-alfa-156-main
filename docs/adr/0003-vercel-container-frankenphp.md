# ADR-0003 — Publicação no Vercel como container FrankenPHP

- **Status:** Aceita
- **Data:** 2026-11-25
- **Fase:** Protótipo

## Contexto

O protótipo precisava de uma URL pública para a banca e os usuários de teste. O
Vercel não executa PHP nativamente.

## Decisão

Empacotar o Laravel numa imagem `dunglas/frankenphp` (PHP 8.4 + Caddy) definida no
`Dockerfile` da raiz e declará-la como serviço `container` no `vercel.json`, com
todo o tráfego reescrito para ela. O Vercel termina o TLS; o Caddy escuta em `$PORT`.

## Alternativas consideradas

- Runtime PHP da comunidade (`vercel-php`) — sem suporte oficial, cold start alto.
- Hospedagem PHP tradicional ou VPS — exige manutenção de servidor.

## Consequências

- A imagem é imutável: `view:cache` e autoload otimizado no build; segredos só por variáveis de ambiente.
- O container é efêmero e sem estado (ver ADR-0005).
- Migrações não rodam no boot (ver ADR-0026).

## Evidência

`Dockerfile`, `vercel.json`, `Caddyfile.vercel`, commit `326362c`.
