# ADR-0004 — Conexão pelo session pooler do Supabase e RLS ligado

- **Status:** Aceita
- **Data:** 2026-11-25
- **Fase:** Protótipo

## Contexto

O Supabase oferece conexão direta (só IPv6), *transaction pooler* (porta 6543) e
*session pooler* (porta 5432). Além disso, toda tabela do schema `public` fica
exposta pela Data API (PostgREST) com a chave pública.

## Decisão

- Usar o *session pooler* (5432) com `sslmode=require`: aceita IPv4 e preserva os
  prepared statements do PDO, que o transaction pooler quebra.
- Habilitar RLS em todas as tabelas, sem políticas para `anon`, para que a Data API
  não exponha chamados. O app acessa como `postgres` pelo pooler e não depende do RLS.

## Consequências

- Número de conexões limitado pelo pooler em modo sessão; suficiente para a demonstração.
- Qualquer tabela nova precisa nascer com RLS ligado no `schema.sql`.

## Evidência

README › Publicar no Vercel (passos 1 e 2), `app/database/supabase/schema.sql`.
