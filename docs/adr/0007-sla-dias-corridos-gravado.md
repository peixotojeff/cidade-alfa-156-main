# ADR-0007 — SLA em dias corridos, do catálogo, gravado no chamado

- **Status:** Aceita
- **Data:** 2026-11-23 (prazos recalibrados em 2026-11-25)
- **Fase:** Protótipo

## Contexto

O objetivo do projeto é tornar o prazo visível. O SP156 não publica prazos; a
Cidade Alfa precisa de uma meta por serviço.

## Decisão

- Cada serviço do catálogo (`config/cidade.php` › `servicos`) tem um prazo em
  **dias corridos**, escolhido pela equipe a partir da distribuição real de tempos
  do SP156 (mediana e p75).
- O prazo é calculado na abertura e **gravado** na coluna `prazo`: mudar o catálogo
  não altera chamados já abertos.

## Alternativas consideradas

- Dias úteis — mais fiel à operação, mas exige calendário de feriados (ver ADR-0022).
- Prazo calculado na leitura — mudaria a promessa feita ao cidadão.

## Consequências

- "% no prazo" mede o serviço contra uma meta fictícia, não oficial; isso é dito em `/indicadores`.
- Iluminação pública tem prazo, mas nenhuma amostra real (não está nos dados abertos).

## Evidência

`app/config/cidade.php`, coluna `demandas.prazo`, `app/database/seeders/data/README.md` › População × amostra.
