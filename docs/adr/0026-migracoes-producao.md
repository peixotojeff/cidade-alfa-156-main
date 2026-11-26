# ADR-0026 — Como aplicar migrações no banco de produção

- **Status:** Proposta
- **Data:** 2026-11-26
- **Fase:** Pós-protótipo

## Contexto

Migrações não rodam no boot do container (ADR-0003); hoje são aplicadas à mão com `php artisan migrate --force` ou pelo `schema.sql`, que precisa ser mantido em paralelo.

## Opções em avaliação

- Job de CI (GitHub Actions) que roda `migrate --force` antes do deploy.
- Migrações SQL do Supabase CLI como fonte única, gerando o `schema.sql`.
- Manter manual e documentar o checklist.

## Quando decidir

Na próxima migração que alterar dados em produção.

## Relacionadas

ADR-0002, ADR-0003, ADR-0004.
