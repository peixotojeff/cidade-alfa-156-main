# ADR-0011 — Design system Skylearn adaptado ao serviço público

- **Status:** Aceita
- **Data:** 2026-11-25
- **Fase:** Protótipo

## Contexto

O público inclui idosos e uso no celular, na rua. O protótipo precisava de uma
identidade consistente e acessível.

## Decisão

Adotar o Skylearn (`docs/design/skylearn-DESIGN.md`) com as adaptações registradas
em `DESIGN.md`: tokens em `:root` de `app/public/cidade.css`, texto ≥ 16px, controles
de 56px, contraste AA (Sky Deep no lugar de Sky), erro/sucesso sempre com ícone +
texto, fontes auto-hospedadas, ícones próprios e **nenhuma gamificação**.

## Consequências

- Toda mudança visual passa pelo `DESIGN.md` (regra também no `CLAUDE.md`).
- Fontes servidas pelo próprio app, sem CDN externo.

## Evidência

`DESIGN.md`, `app/public/cidade.css`, `resources/views/cidade/icone.blade.php`, commit `c8bea4a`.
