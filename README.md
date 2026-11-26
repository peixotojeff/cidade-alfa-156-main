# Cidade Alfa 156+ · Cidadania Digital na Cidade Alfa

**PBL Data Science · FIAP · 1º ano (1TSCOR, 2026/2) · grupo Data_Drivens**
Daniel Lucas Etcheverry · Jefferson Rocha Peixoto · Sheila Bodin Ozorio

> **Desafio:** tornar a **resolutividade** e o **prazo (SLA)** dos chamados de zeladoria visíveis ao cidadão.
> A Cidade Alfa é fictícia e usa como referência real **1,5 milhão de chamados** dos dados abertos do
> SP156 (Prefeitura de São Paulo, licença CC0, jan–set/2026).

| | Link |
| --- | --- |
| **App publicado** | https://app-alpha-three-46.vercel.app |
| **Fase 4 — notebook da EDA (Colab)** | [abrir no Colab](https://colab.research.google.com/github/peixotojeff/cidade-alfa-156-main/blob/main/fase4/eda_sp156.ipynb) · página [`/analise`](https://app-alpha-three-46.vercel.app/analise) |
| **Portfólio de Machine Learning (Colab)** | [abrir no Colab](https://colab.research.google.com/github/peixotojeff/cidade-alfa-156-main/blob/main/portfolio/ml_sp156.ipynb) · página [`/modelos`](https://app-alpha-three-46.vercel.app/modelos) |

## Linha do tempo do projeto

| Fase | Tema | Entrega | Onde está |
| --- | --- | --- | --- |
| 1 | Descoberta e modelo conceitual | Dossiê do problema, persona, jornada, regras de negócio, DER | PDFs entregues (fora deste repositório) |
| 2 | Modelagem e arquitetura | Modelo relacional (tabelas `T_DD_*` no Oracle) | PDFs entregues (fora deste repositório) |
| 3 | Coleta, ingestão e persistência | Arquitetura on-premise + Databricks (Raw/Bronze/Prata/Ouro); **protótipo do app** | [seção Fase 3](#fase-3--arquitetura-de-dados-e-databricks) · [`app/`](app) |
| **4** | **Exploração e preparação de dados** | **Qualidade, higienização, atributos de SLA e EDA** | [`fase4/`](fase4) · [seção Fase 4](#fase-4--exploração-e-preparação-de-dados) |
| — | Portfólio de ML | Regressão, classificação, clusterização, anomalias | [`portfolio/`](portfolio) · [seção Portfólio](#portfólio-de-machine-learning) |
| 5 | Modelagem analítica e visualização | Painéis e modelos | a fazer: a base da Fase 4 já traz os campos |

---

## Fase 4 — Exploração e preparação de dados

Ponte entre os dados persistidos (Fase 3) e os painéis da Fase 5. Detalhes em [`fase4/README.md`](fase4/README.md).

| Pilar | O que foi feito |
| --- | --- |
| **Qualidade** | Diagnóstico de 1.525.907 chamados: 37% sem coordenada, 31% de linhas idênticas não deduplicáveis (a base aberta não tem protocolo), subprefeituras renomeadas no meio do ano, nomes sem acento |
| **Higienização** | Nenhum chamado apagado sem motivo: problemas viram *flags* e cada decisão fica registrada ([tabela](fase4/saida/tabelas/limpeza_decisoes.csv)) |
| **Engenharia de atributos** | `estouro_sla_dias`, `flag_cumpriu_sla`, `faixa_atendimento` (em 24h · no prazo · atraso leve · atraso crítico), outliers (Tukey), backlog crítico, calendário, `score_prioridade_comunidade` |
| **EDA** | Tempo por serviço (média × mediana), geografia, sazonalidade, canais e reclamações repetidas — 9 gráficos |

**Principais achados**

- O prazo varia muito por serviço: **95% no prazo em entulho** e **42% em veículo abandonado** (74% ainda abertos na extração).
- Média muito acima da mediana em todos os serviços (cauda longa): os painéis devem usar **mediana e percentis**.
- **Mais de 25 pontos** de diferença no cumprimento do prazo entre subprefeituras (Jabaquara 86,7% × Vila Mariana 60,4%).
- Nos serviços com local, o **celular é o canal principal** (52,5% dos buracos).
- **14% dos chamados de veículo abandonado repetem** outro já aberto no mesmo dia e ponto: evidência para o **apoio comunitário (RN-03)**.

**Artefatos:** [`pipeline_fase4.py`](fase4/pipeline_fase4.py) (gera tudo e exporta para o app) ·
[`eda_sp156.ipynb`](fase4/eda_sp156.ipynb) (passo a passo para o Colab, autocontido) ·
página `/analise` no app · bases Raw/Prata/Ouro em parquet (geradas localmente, fora do git).

## Portfólio de Machine Learning

Quatro técnicas sobre os 317 mil chamados da camada Ouro da Fase 4. Detalhes em [`portfolio/README.md`](portfolio/README.md).

| Técnica | Pergunta | Resultado no teste |
| --- | --- | --- |
| Regressão | Quantos dias o chamado vai levar? | Gradient Boosting: MAE de 10,0 dias, **−15%** contra a mediana do serviço |
| Classificação | Vai estourar o prazo? | ROC AUC **0,79** em jul–set (período não visto); 86% de acerto no top 10% de risco |
| Clusterização | Perfis de subprefeitura · focos de buraco | K-Means: Ágeis, Intermediárias, Lentas · DBSCAN: 40 focos |
| Anomalias | Dias e coordenadas atípicos | **8 de 8 feriados** encontrados sem calendário · 62 coordenadas suspeitas |

Sem vazamento de dados (só o que se sabe na abertura do chamado), sempre comparado a um baseline
e com validação temporal na classificação.

---

## O app — protótipo funcional (Laravel)

Código em `app/` (Laravel 12, Blade). O cidadão abre um chamado marcando o local no mapa, acompanha
prazo e histórico pelo protocolo e avalia o atendimento no final. A secretaria avança o status e
registra avisos simulados (sem envio real).

| Página | Para quê |
| --- | --- |
| `/` · `/chamados/novo` · `/acompanhar` | abrir e acompanhar chamados; exemplo completo em `/acompanhar/ALFA-0000000000000156` |
| `/indicadores` | transparência: % resolvido no prazo, tempo típico, atrasados e satisfação (NPA = % Excelente − % Péssimo) |
| `/secretaria` | fila priorizada por prazo, com filtros (aberta sem login na versão publicada) |
| `/analise` | Fase 4: passo a passo da EDA |
| `/modelos` | portfólio de Machine Learning |

Identidade visual: [`DESIGN.md`](DESIGN.md) (design system Skylearn adaptado a serviço público).

### Executar localmente (Linux ou WSL)

Instale as dependências com `cd app && composer install`. Use PHP 8.4.1 ou posterior com as
extensões SQLite (`pdo_sqlite`), `mbstring` e XML e volte à raiz do projeto. Rode `bash rodar-demo.sh`
e abra `http://127.0.0.1:8156`. O launcher só migra `app/database/demo.sqlite`; o banco e a chave
de sessão são gerados localmente e não fazem parte do pacote.

- `/secretaria`: localmente, o script a habilita só em loopback. Na versão publicada ela fica aberta
  sem login (`SECRETARIA_ABERTA=true`): qualquer pessoa com o link pode avançar status de chamados
  abertos no app. Os chamados do SP156 são somente leitura. Para proteger com senha, remova essa
  variável e defina `SECRETARIA_USUARIO`/`SECRETARIA_SENHA`.
- O mapa usa Leaflet com imagens do OpenStreetMap; se falharem, dá para digitar coordenadas. A
  geolocalização depende de permissão do navegador e origem segura (`localhost` ou HTTPS).
- Testes: na pasta `app`, rode `php vendor/bin/phpunit` com `APP_KEY` definida com uma chave de teste aleatória.

### Dados reais do SP156 no app

O app traz uma amostra de **600 chamados reais** (100 por serviço, na proporção real de abertos e
resolvidos de cada serviço), usada nos indicadores e na fila da secretaria. O status desses chamados é
o da data de extração de cada planilha. Origem, mapeamento e limitações:
[`app/database/seeders/data/README.md`](app/database/seeders/data/README.md).

- Local: `php artisan db:seed --class=Sp156Seeder`
- Produção: botão "Recarregar amostra do SP156" na secretaria, ou `php artisan cidade:sp156-sql amostra.sql` e executar o SQL no Supabase.

### Publicar no Vercel (container + Supabase)

O Vercel não roda PHP nativamente. O `Dockerfile` da raiz empacota o Laravel com FrankenPHP, e o
`vercel.json` declara esse container como serviço e manda todo o tráfego para ele. No projeto do
Vercel, deixe o *Root Directory* vazio e o *Framework Preset* em **Services** (ou **Container**), sem
overrides de build/output. O SQLite não persiste no Vercel, então o banco de produção é um Postgres do Supabase.

1. **Banco (Supabase).** Crie um projeto e, no *SQL Editor*, execute uma vez [`app/database/supabase/schema.sql`](app/database/supabase/schema.sql). Ele cria as tabelas, registra a migração e liga RLS para a Data API do Supabase não expor os chamados. Depois aplique as migrações seguintes de `app/database/migrations`.
2. **String de conexão.** Em *Connect → Direct → Session pooler*, copie a URI (`postgresql://postgres.<ref>:<senha>@aws-1-<região>.pooler.supabase.com:5432/postgres`) e acrescente `?sslmode=require`. Use o *session pooler* (porta 5432): ele aceita IPv4, e o *transaction pooler* (6543) quebra os prepared statements do PDO.
3. **Projeto no Vercel.** Importe o repositório, com *Root Directory* na raiz (onde está o `Dockerfile`). Em *Settings → Environment Variables*, defina:

   | Variável | Valor |
   | --- | --- |
   | `APP_KEY` | saída de `php artisan key:generate --show`; mantenha fixa |
   | `APP_URL` | URL pública, ex.: `https://cidade-alfa.vercel.app` |
   | `DB_URL` | URI do passo 2 |
   | `SECRETARIA_ABERTA` | `true` abre a `/secretaria` sem login (modo demonstração, usado na versão publicada) |
   | `SECRETARIA_USUARIO` / `SECRETARIA_SENHA` | sem `SECRETARIA_ABERTA`, protegem a `/secretaria` com Basic Auth; sem nenhum dos dois, a tela fica em 404 |

   O `Dockerfile` já define `APP_ENV=production`, `APP_DEBUG=false`, `DB_CONNECTION=pgsql`, sessão em cookie criptografado e `CIDADE_DEMO=false`. **Nunca defina `CIDADE_DEMO=true` no Vercel.**
4. **Deploy.** Faça push ou rode `vercel deploy --prod`. Depois de mudar variáveis, refaça o deploy.

Novas migrações não rodam no boot do container. Rode-as à parte, com `DB_URL` apontando para o
Supabase (`php artisan migrate --force`, requer `pdo_pgsql`) ou aplique o SQL equivalente no Supabase.

---

## Fase 3 — Arquitetura de dados e Databricks

Material: `Data_Drivens_PBL_Fase3_.zip` (quatro PDFs entregues).

| Entrega | O que o material documenta |
| --- | --- |
| 1. Componentes | Equipe, desafio e foco em tornar a resolução das solicitações e seus prazos visíveis ao cidadão. |
| 2. Arquitetura de dados | Dados sensíveis no ambiente local (Oracle) e dados pseudonimizados (hash com sal, sem identificadores, coordenadas com 3 casas) enviados ao Databricks, com camadas Raw, Bronze, Prata e Ouro e consumo pelo Power BI. |
| 3. Implantação Big Data | Databricks Free Edition, catálogo `cidadania_digital_cidade_alfa`, schemas Bronze/Prata/Ouro e cinco CSVs sintéticos no volume `arquivos_raw`. |
| 4. Tabela Delta e SQL | Criação da tabela Bronze `tb_cadastro_cidadao` e demonstração de `SELECT`, `INSERT`, `UPDATE` e `DELETE`. |

**Como o app e a Fase 4 se encaixam:** o app é a camada de fontes e canais (cidadão e secretaria); na
demonstração, o Supabase faz o papel do banco operacional. A Fase 4 aplica o padrão medalhão aos dados
reais do SP156 (bruto → limpo → atributos de SLA), e os indicadores do app reproduzem os da camada Ouro.

**Limite da evidência:** os PDFs documentam a carga Raw e operações em uma tabela Bronze. A criação dos
schemas Prata e Ouro não demonstra que eles estejam populados; o Power BI aparece no desenho, sem
demonstração de funcionamento.

## Fase 1 — Da ideação ao modelo conceitual

Guia: *Cap. 1 — PBL — Construindo a Ponte entre Problemas e Ideias: A Magia da Ideação*. O caso
**ReciclaData** (coleta de resíduos na Cidade Alfa) é o exemplo do guia, não o desafio do grupo.
As entregas seguem o template do exercício guiado, exportadas em PDF com a nomenclatura
`N_<grupo>_<entrega>.pdf`.
