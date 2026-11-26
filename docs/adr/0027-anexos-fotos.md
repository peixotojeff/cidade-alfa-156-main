# ADR-0027 — Fotos e anexos no chamado

- **Status:** Proposta
- **Data:** 2026-11-26
- **Fase:** Pós-protótipo

## Contexto

Foto foi deliberadamente deixada de fora do protótipo. Ela ajuda a triagem, mas traz custo de armazenamento, moderação e risco de dado pessoal (rostos, placas, EXIF com localização da casa).

## Opções em avaliação

- Supabase Storage com bucket privado e URLs assinadas.
- Vercel Blob privado.
- Em qualquer opção: remover EXIF, limitar tamanho e tipo, e definir retenção.

## Quando decidir

Quando o fluxo da secretaria pedir evidência visual (abertura ou conclusão).

## Relacionadas

ADR-0005, ADR-0024.
