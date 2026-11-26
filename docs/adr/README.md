# Registro de decisões de arquitetura (ADRs)

Cada ADR registra **uma** decisão: o contexto, o que foi escolhido, as
alternativas e as consequências. Decisões tomadas não são editadas; quando mudam,
um novo ADR as substitui e o antigo passa a "Substituída por ADR-XXXX".

Use o [modelo](0000-modelo.md) e o próximo número livre.

## Placar

| Situação | Quantidade |
| --- | ---: |
| Aceitas | **16** |
| ↳ das quais provisórias, com substituta já proposta | 2 (ADR-0008, ADR-0015) |
| ↳ das quais com implementação parcial | 1 (ADR-0013) |
| Propostas (decisões a tomar) | **11** |
| Substituídas ou descartadas | 0 |
| **Total** | **27** |

Por fase, as aceitas se distribuem assim: Fase 3: 1 · Protótipo: 13 · Fase 4: 2
(as ADR-0010 e 0016 servem ao protótipo e à Fase 4 e foram contadas no protótipo).

## Decisões aceitas

| ADR | Decisão | Área | Data |
| --- | --- | --- | --- |
| [0001](0001-monolito-laravel-blade.md) | Monólito Laravel 12 com Blade | Aplicação | 2026-11-23 |
| [0002](0002-sqlite-local-postgres-supabase.md) | SQLite local, Postgres (Supabase) em produção | Dados | 2026-11-25 |
| [0003](0003-vercel-container-frankenphp.md) | Vercel com container FrankenPHP | Infraestrutura | 2026-11-25 |
| [0004](0004-supabase-session-pooler-rls.md) | Session pooler do Supabase e RLS ligado | Infraestrutura / segurança | 2026-11-25 |
| [0005](0005-container-sem-estado.md) | Container sem estado (cookie, cache em memória, fila síncrona) | Infraestrutura | 2026-11-25 |
| [0006](0006-protocolo-aleatorio-sem-login.md) | Acompanhamento por protocolo aleatório, sem login nem CPF | Domínio / privacidade | 2026-11-23 |
| [0007](0007-sla-dias-corridos-gravado.md) | SLA em dias corridos, gravado no chamado | Domínio | 2026-11-23 |
| [0008](0008-acesso-secretaria-tres-modos.md) | Acesso à secretaria em três modos, 404 por padrão *(provisória)* | Segurança | 2026-11-23 |
| [0009](0009-fluxo-status-notificacao-simulada.md) | Fluxo linear de status com notificação simulada | Domínio | 2026-11-23 |
| [0010](0010-amostra-real-sp156.md) | Amostra real e estratificada do SP156 para os indicadores | Dados | 2026-11-25 |
| [0011](0011-design-system-skylearn-adaptado.md) | Design system Skylearn adaptado ao serviço público | Interface | 2026-11-25 |
| [0012](0012-avaliacao-npa.md) | Avaliação em três níveis e NPA | Domínio | 2026-11-25 |
| [0013](0013-arquitetura-medalhao-databricks.md) | Dados sensíveis locais, medalhão pseudonimizado no Databricks *(parcial)* | Dados | Fase 3 |
| [0014](0014-higienizacao-por-flags.md) | Higienização por flags e métricas robustas | Dados | 2026-11-26 |
| [0015](0015-score-prioridade-proxy.md) | Score de prioridade com reclamações repetidas como proxy *(provisória)* | Dados | 2026-11-26 |
| [0016](0016-indicadores-no-app.md) | Indicadores e análise publicados no próprio app | Aplicação / dados | 2026-11-26 |

## Decisões propostas (a tomar)

Ordenadas pelo que bloqueia o quê. Os pré-requisitos de cada uma estão no próprio ADR.

| ADR | Decisão a tomar | Bloqueia | Quando |
| --- | --- | --- | --- |
| [0017](0017-autenticacao-servidores.md) | Autenticação e papéis dos servidores da secretaria | uso com dados reais | antes de qualquer piloto |
| [0024](0024-identificacao-cidadao-lgpd.md) | Identificação do cidadão, consentimento e retenção (LGPD) | 0018, 0020 | antes de notificar ou aceitar apoios |
| [0025](0025-der-fase1-schema-app.md) | Alinhar o DER da Fase 1 com o esquema do app | entrega final | antes da entrega final |
| [0019](0019-paineis-fase5.md) | Ferramenta dos painéis da Fase 5 | 0021 | início da Fase 5 |
| [0021](0021-integracao-app-databricks.md) | Integração do app com a plataforma de dados | — | com a 0019 |
| [0026](0026-migracoes-producao.md) | Como aplicar migrações em produção | — | próxima migração em produção |
| [0018](0018-notificacoes-reais.md) | Envio real de notificações | — | depois da 0024 |
| [0020](0020-apoio-comunitario.md) | Apoio comunitário real (RN-03) | — | depois da 0024 |
| [0022](0022-sla-dias-uteis.md) | SLA em dias úteis e prazo por secretaria | — | com uma secretaria real |
| [0023](0023-api-app-movel.md) | API pública para app móvel ou integração com o 156 | — | se houver app nativo |
| [0027](0027-anexos-fotos.md) | Fotos e anexos no chamado | — | quando a triagem pedir |

## Mapa de dependências

```
0008 (secretaria provisória) ──substituída por──> 0017
0006 (anônimo) ──> 0024 (identificação/LGPD) ──> 0018 (notificações)
                                              └─> 0020 (apoios) ──substitui──> 0015
0013 (medalhão) ──> 0019 (painéis Fase 5) ──> 0021 (integração app → Databricks)
0002 + 0003 ──> 0026 (migrações)
```
