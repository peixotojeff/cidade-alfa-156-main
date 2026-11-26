# ADR-0015 — Score de prioridade comunitária com reclamações repetidas como proxy

- **Status:** Aceita (provisória — ver ADR-0020)
- **Data:** 2026-11-26
- **Fase:** 4

## Contexto

A regra RN-03 prevê apoio comunitário, mas a base aberta não tem apoios.

## Decisão

`score_prioridade_comunidade = urgencia_base × (1 + log2(1 + apoios_proxy))`, onde
`apoios_proxy` = repetições do mesmo serviço no mesmo dia e ponto, e `urgencia_base`
vai de 1 a 3 pelo risco à segurança. O `log2` amortece concentrações.

## Consequências

- O máximo (73 repetições no mesmo ponto) sugere coordenada padrão de geocodificação:
  investigar antes de usar o score em produção.
- Quando houver apoios reais, troca-se só a variável de entrada.

## Evidência

`fase4/README.md` › Score de prioridade comunitária.
