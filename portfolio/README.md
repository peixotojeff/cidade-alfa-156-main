# Portfólio de Machine Learning — Cidade Alfa 156+

Regressão, classificação, clusterização e detecção de anomalias com **317 mil chamados
reais** de zeladoria urbana (dados abertos do SP156, Prefeitura de São Paulo, licença CC0,
jan–set/2026), preparados na [Fase 4](../fase4/README.md).

- **Notebook:** [`ml_sp156.ipynb`](ml_sp156.ipynb) · [abrir no Colab](https://colab.research.google.com/github/peixotojeff/cidade-alfa-156-main/blob/main/portfolio/ml_sp156.ipynb)
- **Página no app:** [`/modelos`](https://app-alpha-three-46.vercel.app/modelos)

## Resultados

| Técnica | Pergunta | Resultado (teste) |
| --- | --- | --- |
| Regressão | Quantos dias o chamado vai levar? | Gradient Boosting: MAE 10,0 dias (−15% vs. mediana do serviço), R² 0,64 em log |
| Classificação | Vai estourar o prazo? | Gradient Boosting: ROC AUC 0,79 em jul–set (baseline 0,775); 86% de acerto no top 10% de risco |
| Clusterização | Perfis de subprefeitura · focos de buraco | K-Means k=3 (silhueta 0,31): Ágeis, Intermediárias, Lentas · DBSCAN: 40 focos |
| Anomalias | Dias e coordenadas fora do padrão | 8 de 8 feriados redescobertos sem calendário · 62 coordenadas suspeitas |

Os números exatos ficam em `app/public/portfolio/metricas.json`, gerado pelo notebook.

## Decisões metodológicas (e erros corrigidos)

- **Sem vazamento:** entradas só com o que se sabe na abertura (serviço, subprefeitura,
  canal, coordenada, calendário, `carga_7d` = chamados dos 7 dias **anteriores**).
- **Baseline em tudo:** cada modelo é comparado a uma regra simples.
- **Validação temporal** na classificação (treino jan–jun, teste jul–set).
- **Censura na regressão:** sem filtro, o `mês` virava a 2ª variável mais importante
  ("quem abriu em setembro e já foi resolvido é rápido"). Corrigido usando só chamados
  com ≥ 90 dias de observação.
- **DBSCAN:** com raio de 400 m os focos se encadeavam (91% dos chamados em "foco").
  Calibrado para 200 m e 60 chamados.
- **K-Means:** k limitado a 6 (32 subprefeituras); k=2 tem silhueta maior mas só separa
  um pequeno grupo, então usamos a melhor silhueta com k ≥ 3. A curva completa está no notebook.
- **Classificação:** o ganho sobre o baseline é modesto e está dito assim: a maior parte
  do risco vem do tipo de serviço.

## Reproduzir

```bash
fase4/.venv/Scripts/python -I -m pip install -r fase4/requirements.txt scikit-learn
fase4/.venv/Scripts/python -I portfolio/preparar_dados.py          # base compacta (requer as saídas da Fase 4)
fase4/.venv/Scripts/python portfolio/construir_notebook.py --executar
```

| Arquivo | Conteúdo |
| --- | --- |
| `dados/sp156_modelagem.parquet` | 317.482 chamados, 2,3 MB; sem endereço, coordenadas com 3 casas (~100 m) |
| `dados/volume_diario.csv` | chamados por dia, total e por serviço |
| `preparar_dados.py` | gera a base compacta a partir da camada Ouro da Fase 4 |
| `construir_notebook.py` | gera o notebook; com `--executar`, roda e publica figuras e métricas no app |

No Colab, o notebook baixa a base direto do GitHub; não precisa das planilhas originais.
