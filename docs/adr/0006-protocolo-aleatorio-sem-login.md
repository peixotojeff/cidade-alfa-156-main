# ADR-0006 — Acompanhamento por protocolo aleatório, sem login nem CPF

- **Status:** Aceita
- **Data:** 2026-11-23
- **Fase:** Protótipo

## Contexto

Exigir cadastro cria uma barreira para quem só quer relatar um problema. Coletar CPF no
protótipo traria obrigações da LGPD sem necessidade para a demonstração.

## Decisão

Cada chamado recebe um protocolo `ALFA-` + 16 hexadecimais gerados por
`random_bytes(8)` (64 bits). Quem tem o protocolo acompanha o chamado; não há conta
de cidadão nem dado pessoal.

## Alternativas consideradas

- Protocolo sequencial — permitiria enumerar chamados de terceiros.
- Login gov.br / CPF — adiado (ver ADR-0024).

## Consequências

- O protocolo funciona como segredo: perdido, não há recuperação.
- O formato é validado por regex antes de consultar o banco (`/^ALFA-[A-F0-9]{16}$/`).
- Sem identidade, não há "meus chamados" nem notificação ativa ao cidadão.

## Evidência

`DemandaController::abrir` e `buscar`, migração `2026_11_23_000000_create_demandas.php` (`protocolo` único).
