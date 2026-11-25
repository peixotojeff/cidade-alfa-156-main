# Identidade visual — Cidade Alfa 156+

A identidade visual do Cidade Alfa 156+ segue o design system **Skylearn**
([docs/design/skylearn-DESIGN.md](docs/design/skylearn-DESIGN.md)). Este arquivo
registra **como** ele foi adaptado para um serviço público usado por adultos e
quais decisões prevalecem quando o original não se aplica.

O Skylearn foi escrito para ensino infantil. Daqui ele herda o que vale para
qualquer cidadão: calor visual sem infantilizar, tipografia acessível, cantos
suaves, alvos de toque grandes, erros tratados com gentileza e acessibilidade
como requisito. A gamificação fica de fora.

Os tokens vivem em [`app/public/cidade.css`](app/public/cidade.css), no bloco
`:root`. Use sempre as variáveis; não escreva cores ou raios soltos.

## O que se aplica como está

- **Paleta**: Sky, Sun, Leaf, Coral, Berry, superfícies, Ink e Outline, com os
  mesmos hexadecimais do Skylearn.
- **Tipografia**: Hubot Sans (títulos), Atkinson Hyperlegible (texto e
  interface), JetBrains Mono (protocolos, coordenadas, datas em tabela). Nada
  abaixo de 16px. Texto corrido em 18px, com `letter-spacing: .01em`.
- **Raios**: 8px em chips, 12px em campos, 16px em botões, 20px em cartões,
  28px em painéis grandes, 999px em pílulas.
- **Elevação**: cartões com borda Outline; sombra suave só em elementos
  interativos ao passar o mouse.
- **Espaçamento**: base de 4px; contêiner de até 1280px; 48px entre seções.
- **Movimento**: 240ms com a curva de mola do Skylearn; barra de progresso em
  480ms ease-out; `prefers-reduced-motion` reduz tudo a 120ms ou nada.
- **Erros**: Coral, nunca vermelho forte, sempre com ícone e texto (cor nunca é
  o único sinal) e sem bloquear o campo.

## Adaptações para o serviço público

| Skylearn | Cidade Alfa 156+ | Motivo |
| --- | --- | --- |
| Botão primário com fundo Sky `#3B82F6` | Fundo **Sky Deep** `#1D4ED8`, hover `#1E40AF` | Branco sobre Sky tem contraste 3,7:1; sobre Sky Deep, 6,7:1. O próprio Skylearn exige AA ou mais. |
| Links em Sky | Links em Sky Deep | Mesmo motivo: texto pequeno precisa de 4,5:1. |
| Texto em Coral ou Leaf | Texto em Ink sobre fundo Coral Soft / Leaf Soft, com ícone colorido | Coral e Leaf não atingem 4,5:1 como texto sobre branco. |
| Alvo de toque de 56px (mãos pequenas) | 56px nos controles principais; 48px no mínimo | O público inclui idosos e uso em celular na rua. |
| Estrelas, sequência, confete, mascote | **Não usar** | Gamificar um chamado de serviço público banaliza o problema do cidadão. |
| Sun para conquistas | Sun só para destaque de **prazo** (ícone de relógio) | Sun continua fora de ações primárias. |
| Leaf para progresso e acerto | Leaf para **avanço do chamado** e chamado **resolvido** | Mesmo significado: progresso concluído. |
| Berry para "ir além" | Berry para o status **Em atendimento** | Diferencia visualmente das etapas Recebida (Sky) e Resolvida (Leaf). |
| Modo kid / modo pais | Página do cidadão / painel da secretaria | O painel usa tipografia mais densa e dados em mono, como o "modo pais". |

## Páginas e navegação

Cada página atende a um único objetivo. O menu do topo lista as páginas e
marca a atual com `aria-current="page"` (fundo Sky Soft). No celular, o menu
vira uma grade 2×2 com botões de 48px.

| Página | Rota | Objetivo | Ação principal |
| --- | --- | --- | --- |
| Início | `/` | Escolher o que fazer; conhecer serviços e prazos | Abrir um chamado |
| Abrir chamado | `/chamados/novo` | Registrar em 3 etapas: serviço, descrição, local | Registrar chamado |
| Acompanhar | `/acompanhar` | Consultar pelo protocolo | Consultar |
| Seu chamado | `/acompanhar/{protocolo}` | Ver status, prazo, progresso e detalhes | Abrir outro chamado |
| Indicadores | `/indicadores` | Transparência: resolutividade no prazo e tempo de atendimento por serviço | — |
| Secretaria | `/secretaria` | Resumo, indicadores e fila filtrável, priorizada por prazo (só com acesso) | Avançar status |

Regras:

- Uma ação primária (Sky Deep) por tela; as demais são secundárias ou links.
- O serviço é escolhido em cartões de opção (o *Answer Tile* do Skylearn),
  com ícone, exemplo e prazo, e não em uma lista suspensa.
- As coordenadas ficam recolhidas em "Prefiro digitar as coordenadas" e abrem
  sozinhas quando o mapa ou o GPS falham, ou quando há erro nelas.
- Depois de registrar, o cidadão cai na página do chamado com a confirmação
  "Recebemos seu chamado." e o protocolo em destaque.

## Status do chamado

| Status | Cor | Ícone | Texto |
| --- | --- | --- | --- |
| Recebida | Sky Soft / Sky Deep | caixa de entrada | "Recebida" |
| Em atendimento | Berry Soft / Berry | ferramenta | "Em atendimento" |
| Resolvida | Leaf Soft / Leaf Deep | check | "Resolvida" |

A página de acompanhamento mostra uma barra de progresso (Sky Soft com
preenchimento Leaf) e a linha do tempo com ícone e data em cada etapa. Etapa
concluída sem data registrada mostra "Data não informada"; nunca invente datas.

## Prazo (SLA)

O objetivo do projeto é tornar a resolutividade e o SLA visíveis ao cidadão.
Toda tela que mostra um chamado mostra também a situação do prazo:

| Situação | Cor | Ícone |
| --- | --- | --- |
| No prazo | Sky Soft / Sky Deep | relógio |
| Atrasado | Coral Soft / texto `#991B1B` | alerta |
| Resolvido no prazo (· N dias) | Leaf Soft / Leaf Ink | check |
| Resolvido fora do prazo (· N dias) | Sun Soft / texto `#92400E` | relógio |

No medidor de resolutividade, a cor segue a faixa: Leaf a partir de 80%, Sun de
50% a 79% e Coral abaixo de 50%, sempre com o percentual escrito ao lado.

## Tom de voz

Siga a seção *Voice and Tone* do Skylearn, trocando a criança pelo cidadão:

- Direto e cordial: "Recebemos seu chamado." e não "Sua solicitação foi
  processada com sucesso."
- Específico: "Prazo previsto: 3 dias corridos." e não "Em breve."
- Erros orientam o próximo passo: "Marque o local no mapa ou digite as
  coordenadas."
- Estados vazios convidam: "Nenhum chamado por aqui ainda."
- Sem exclamações em excesso e sem emoji.
