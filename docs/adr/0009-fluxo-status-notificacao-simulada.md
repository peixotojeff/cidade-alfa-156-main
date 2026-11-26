# ADR-0009 — Fluxo linear de status com notificação simulada na mesma transação

- **Status:** Aceita
- **Data:** 2026-11-23
- **Fase:** Protótipo

## Contexto

O cidadão precisa ver o histórico do chamado e saber que foi avisado. Integrar
WhatsApp ou SMS de verdade custa e exige identificação do cidadão.

## Decisão

- Status seguem Recebida → Em atendimento → Resolvida; saltos e retrocessos são rejeitados.
- Cada mudança grava uma linha em `historico_status` com `notificacao_simulada = true`,
  na mesma transação que atualiza a demanda. Nada é enviado.

## Consequências

- Histórico e "aviso" nunca divergem.
- A tela mostra "Aviso ao cidadão (simulado)" para não prometer o que não existe.
- Envio real fica para o ADR-0018.

## Evidência

`DemandaController::atualizar` (`DB::transaction`), tabela `historico_status`, spec de 2026-11-23.
