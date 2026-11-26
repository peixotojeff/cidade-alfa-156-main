# Fase 4 — Exploração e Preparação de Dados

Cidade Alfa 156+ · Data_Drivens · ponte entre os dados persistidos (Fase 3) e os
painéis e modelos analíticos (Fase 5).

**Base:** dados abertos do SP156 (Prefeitura de São Paulo, licença CC0),
1º a 3º trimestre de 2026 — **1.525.907 chamados**. A Cidade Alfa usa São Paulo
como referência real; os prazos (SLA) são os do catálogo do app
(`app/config/cidade.php`).

## Onde ver

| Formato | Onde | Para quê |
| --- | --- | --- |
| **Notebook (Google Colab)** | [`eda_sp156.ipynb`](eda_sp156.ipynb) · [abrir no Colab](https://colab.research.google.com/github/peixotojeff/cidade-alfa-156-main/blob/main/fase4/eda_sp156.ipynb) | passo a passo comentado; roda sozinho no Colab (baixa as planilhas do portal) |
| **Página no app** | `/analise` (menu "Análise") | versão visual para a apresentação |
| **Pipeline** | [`pipeline_fase4.py`](pipeline_fase4.py) | gera tabelas, figuras e as bases; exporta para a página do app |

O notebook é gerado por [`construir_notebook.py`](construir_notebook.py). Para
atualizá-lo **com as saídas** (gráficos visíveis no GitHub e no Colab):

```bash
fase4/.venv/Scripts/python -I -m pip install nbclient ipykernel nbformat
fase4/.venv/Scripts/python fase4/construir_notebook.py --executar "<pasta com as planilhas>"
```

Sem `--executar`, o notebook sai sem saídas. Nos dois casos, uma cópia vai para
`app/public/fase4/` (download na página `/analise`).

## Como reproduzir

```bash
python -m venv fase4/.venv
fase4/.venv/Scripts/python -m pip install -r fase4/requirements.txt
fase4/.venv/Scripts/python -I fase4/pipeline_fase4.py "<pasta com arquivo-final-{1,2,3}-trim-26.xlsx>"
```

| Saída | Conteúdo | Versionado |
| --- | --- | --- |
| `saida/tabelas/*.csv` | qualidade, decisões de limpeza, estatísticas da EDA | sim |
| `saida/figuras/*.png` | 9 gráficos para a apresentação | sim |
| `saida/resumo.json` | números-chave | sim |
| `saida/dados/sp156_bruto.parquet` | cópia fiel das planilhas (camada Raw) | não (31 MB) |
| `saida/dados/sp156_limpo.parquet` | base limpa, todos os chamados (Prata) | não (33 MB) |
| `saida/dados/sp156_sla_atributos.parquet` | 6 serviços do app com atributos de SLA (Ouro) | não (11 MB) |

As três bases seguem o padrão medalhão da arquitetura da Fase 3 (Raw → Prata →
Ouro) e podem ser carregadas como tabelas Delta no catálogo
`cidadania_digital_cidade_alfa`.

## 1. Qualidade dos dados (diagnóstico antes da limpeza)

| Verificação | Resultado |
| --- | --- |
| Sem data de abertura | 0 |
| Finalização antes da abertura | 0 |
| Finalizada sem data de finalização | 4.596 |
| Sem coordenadas | 564.810 (37%) — serviços sem local (ex.: RGA, ouvidoria) |
| Coordenadas fora do município | 2.355 |
| Linhas idênticas em todas as colunas | 481.162 (31%) — **não deduplicáveis**: a base aberta não traz protocolo, e pedidos diferentes sem endereço ficam idênticos |
| Status | 1.313.488 finalizadas · 192.000 abertas · 12.478 em andamento · 7.940 canceladas · 1 em aprovação |
| Nomes de subprefeitura | sem acento ("Se", "Butanta"); **dois nomes trocados no meio do ano** (Perus → Perus-Anhanguera; Casa Verde-Cachoeirinha → Casa Verde-Limão-Cachoeirinha); 4.266 valores que não são território |

Detalhe: `saida/tabelas/qualidade_nulos_por_coluna.csv` e `qualidade_prefeituras_invalidas.csv`.

## 2. Higienização — decisões

Nenhum chamado é apagado sem motivo; problemas viram **flags** para que cada
métrica escolha o que excluir. Registro completo em `saida/tabelas/limpeza_decisoes.csv`.

| Problema | Linhas | Decisão |
| --- | ---: | --- |
| Finalizada sem data de finalização | 4.596 | mantida; fora do tempo de atendimento |
| Coordenada ausente ou fora do município | 567.165 | coordenada anulada; `flag_coord_valida = 0` |
| Número predial inválido (> 25.000 ou não numérico) | 912 | número anulado |
| Subprefeitura com nome antigo | 5.280 | unificada no nome atual |
| "Prefeitura operacional" que é órgão ou "não encontrada" | 4.266 | anulada |
| Grafia sem acento | — | dicionário das 32 subprefeituras com a grafia oficial |
| **Duplicata provável** (mesmo serviço, mesmo dia, mesmo ponto) | 110.053 | mantida com flag; **fora das métricas de SLA** |
| Outlier de tempo (Tukey: > Q3 + 1,5·IQR, por serviço) | 15.880 | mantido com flag; médias gerenciais usam tempo **winsorizado no p99** |
| Backlog crítico (aberto há > 100 dias na data de corte) | 26.742 | mantido com flag; reportado à parte |

**Por que não descartar outliers:** os tempos longos são reais (um buraco que
levou 200 dias aconteceu). Descartar esconderia exatamente o problema que o
projeto quer tornar visível. Por isso as métricas usam **mediana** e média
winsorizada, e os casos extremos ganham flag para análise própria.

## 3. Engenharia de atributos (base `sp156_sla_atributos`)

317.482 chamados dos 6 serviços do app (finalizados, abertos e em andamento;
sem canceladas, sem duplicatas prováveis).

| Atributo | Definição |
| --- | --- |
| `sla_dias` / `data_limite_sla` | prazo do catálogo da Cidade Alfa; `data_abertura + sla_dias` |
| `data_corte` | data de extração da planilha (06/08/2026 jan–jun; 08/10/2026 jul–set) |
| `data_referencia` | conclusão (finalizados) ou data de corte (abertos) |
| `tempo_atendimento_dias` | `data_finalizacao − data_abertura` (finalizados) |
| `idade_na_corte_dias` | `data_corte − data_abertura` (abertos) |
| **`estouro_sla_dias`** | `data_referencia − data_limite_sla` (positivo = dias de atraso) |
| **`flag_cumpriu_sla`** | 1 = finalizado no prazo; 0 = atrasou (finalizado ou aberto com prazo vencido); vazio = aberto ainda no prazo |
| **`faixa_atendimento`** | Atendido em 24h (≤ 1 dia) · No prazo · Atraso leve (até o dobro do prazo) · Atraso crítico (mais que o dobro) · Em aberto (no prazo / atrasado) |
| `flag_outlier_tempo`, `tempo_atendimento_wins_dias` | outlier de Tukey por serviço; tempo limitado ao p99 do serviço |
| `flag_backlog_critico` | aberto há mais de 100 dias na data de corte |
| `dia_semana`, `dia_mes`, `semana_do_mes`, `ano_mes`, `flag_fim_de_semana` | calendário da abertura (a base não tem hora) |
| `repeticoes_mesmo_ponto` / `apoios_proxy` | quantas vezes o mesmo problema foi registrado de novo no mesmo dia e ponto |
| **`score_prioridade_comunidade`** | ver abaixo |

### Score de prioridade comunitária (RN-03)

A base aberta **não tem apoios** (`T_DD_APOIO_DEMANDA` só existe no modelo do
projeto). Como aproximação honesta, usamos as **reclamações repetidas do mesmo
problema**: cada repetição é, na prática, mais um cidadão apoiando o chamado.

```
score_prioridade_comunidade = urgencia_base × (1 + log2(1 + apoios))
```

- `urgencia_base` (1 a 3) pelo risco à segurança: árvore e buraco = 3; bueiro e
  entulho = 2; guias e veículo = 1.
- O `log2` evita que um chamado com dezenas de repetições dispare sozinho:
  0 apoios → ×1; 1 → ×2; 3 → ×3; 7 → ×4.
- Resultado: 4,7% dos chamados têm ao menos uma repetição; o máximo é 73 no
  mesmo ponto e dia — **provável coordenada padrão de geocodificação**, a
  investigar antes de usar o score em produção.
- Quando o app registrar apoios reais (RN-03), basta trocar `apoios_proxy`
  pela contagem de `T_DD_APOIO_DEMANDA`.

## 4. Análise exploratória — principais achados

**Tempo de atendimento** (`01_tempo_atendimento_por_servico.png`, `eda_tempo_por_servico.csv`)

| Serviço | Finalizados | Média | Média wins. | Mediana | Desvio | p90 | Prazo | No prazo | Abertos na corte |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| Entulho | 112.355 | 2,0 | 1,9 | 1 | 4,1 | 4 | 5 | **94,9%** | 2,4% |
| Bueiro | 16.159 | 4,9 | 4,8 | 1 | 8,2 | 18 | 7 | 80,2% | 8,5% |
| Buraco na via | 56.625 | 14,7 | 14,4 | 7 | 20,3 | 41 | 7 | 50,7% | 27,6% |
| Árvore | 42.709 | 27,4 | 27,2 | 17 | 29,6 | 68 | 30 | 68,8% | 20,7% |
| Guias e sarjetas | 12.005 | 34,8 | 34,6 | 21 | 37,6 | 83 | 20 | 49,7% | 48,4% |
| Veículo abandonado | 7.556 | 46,4 | 46,2 | 36 | 37,3 | 99 | 30 | **41,5%** | **74,3%** |

- Média ≫ mediana em todos os serviços: distribuição com **cauda longa à direita**.
  Por isso o painel deve usar mediana e percentis, não média simples.
- O SLA do lixo urbano (5 dias) é folgado para a operação real (mediana de 1 dia);
  o de veículo abandonado (30 dias) é estourado na maioria dos casos.

**Faixas de atendimento** (`02_faixas_de_atendimento.png`): entulho e bueiro
resolvem mais da metade em até 24h; em buraco e guias, cerca de um terço termina
em **atraso crítico** (mais que o dobro do prazo).

**Geografia** (`03`, `04`): Sé (98 mil), Mooca (69 mil) e Butantã (45 mil)
concentram o volume. No prazo, o melhor desempenho é Jabaquara (86,7%) e o pior
Vila Mariana (60,4%) — uma diferença de 26 pontos entre subprefeituras.

**Sazonalidade** (`05`, `06`, `07`): o fim de semana tem só 15,6% dos chamados
(metade do volume de um dia útil). O volume semanal cai de ~52 mil (pico na
semana de 12/01) para ~30–35 mil no inverno e volta a subir em agosto.

**Canais** (`08`): na base toda, Portal 48,7%, CRM (central 156) 32,0%, Mobile
12,8%, Integração 3,3%, Chatbot 3,1%. **Nos 6 serviços com local, o Mobile
lidera** (ex.: 52,5% dos buracos), o que reforça um app com mapa e geolocalização.

**Reclamações repetidas** (`09`): 14,2% dos chamados de veículo abandonado
repetem um já aberto no mesmo dia e ponto (6,3% em buraco). É a evidência
quantitativa para o **apoio comunitário (RN-03)**: em vez de abrir chamados
duplicados, o cidadão apoia o existente — menos retrabalho e um sinal de prioridade.

## Limitações

- Sem hora de abertura: não há análise por hora do dia.
- Sem protocolo: duplicatas exatas sem local não podem ser confirmadas.
- O status dos abertos é o da data de extração de cada planilha.
- Distritos seguem sem acento (a fonte não tem; só as 32 subprefeituras foram corrigidas).
- Não há população por subprefeitura nesta base; volumes não estão normalizados por habitante (sugestão para a Fase 5).

## Ponte para a Fase 5

A base `sp156_sla_atributos` já traz os campos que os painéis precisam
(`flag_cumpriu_sla`, `estouro_sla_dias`, `faixa_atendimento`, `score_prioridade_comunidade`,
calendário e geografia). Sugestões de visual: mapa de calor por subprefeitura,
funil de faixas por serviço, tendência semanal de % no prazo e ranking por score.
