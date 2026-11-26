# ADR-0010 — Indicadores alimentados por amostra real e estratificada do SP156

- **Status:** Aceita
- **Data:** 2026-11-25
- **Fase:** Protótipo / Fase 4

## Contexto

Indicadores de SLA com dados inventados não convencem. A Prefeitura de São Paulo
publica os chamados do SP156 sob CC0 e sem dados pessoais; a Cidade Alfa usa São
Paulo como referência.

## Decisão

- Importar 600 chamados reais (100 por serviço mapeado), sorteados com semente fixa
  (156) e **estratificados pela proporção real de status** de cada serviço.
- Marcar a origem (`demandas.origem = 'sp156'`). Esses chamados são **somente
  leitura** (409 no servidor) e não recebem avaliação.
- Medir o atraso deles na **data de corte** de cada planilha (`sp156_corte`), não hoje.
- Carga reprodutível: `scripts/amostra_sp156.py` → CSV versionado → `Sp156Seeder`
  (idempotente, só substitui `origem = 'sp156'`), também acionável pela secretaria.

## Consequências

- A amostra tem o mesmo peso por serviço; o volume real difere muito (documentado).
- Sem hora nem data de início do atendimento na fonte.

## Evidência

`app/database/seeders/data/README.md`, `scripts/amostra_sp156.py`, `app/app/Support/Sp156Amostra.php`, commits `c8bea4a`, `faa3d5a`, `9d48905`, `47762f5`.
