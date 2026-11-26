# ADR-0002 — SQLite na demonstração local, Postgres (Supabase) em produção

- **Status:** Aceita
- **Data:** 2026-11-25
- **Fase:** Protótipo

## Contexto

A demonstração local precisa subir com um comando, sem servidor de banco. O
ambiente publicado (Vercel, ADR-0003) não tem disco persistente, então o SQLite
seria perdido a cada deploy ou nova instância.

## Decisão

- Local: SQLite em `app/database/demo.sqlite`, criado e migrado por `rodar-demo.sh`.
- Produção: Postgres gerenciado do Supabase, configurado por `DB_URL`.
- O esquema é um só (migrações Laravel); `app/database/supabase/schema.sql` é a
  versão SQL para criar o banco no Supabase pelo SQL Editor.

## Alternativas consideradas

- Oracle (usado no DER da Fase 1) — sem camada gratuita prática para publicar o protótipo.
- Postgres também local — aumenta o atrito da demonstração.

## Consequências

- O código evita SQL específico de um banco; testes rodam em SQLite.
- `schema.sql` precisa acompanhar cada nova migração (ver ADR-0026).
- O modelo físico do app difere do DER da Fase 1 (ver ADR-0025).

## Evidência

`rodar-demo.sh`, `Dockerfile` (`DB_CONNECTION=pgsql`), `app/database/supabase/schema.sql`, README › Publicar no Vercel.
