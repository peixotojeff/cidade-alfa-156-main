# Amostra do SP156

`sp156-amostra.csv` tem **600 chamados reais** (100 por serviço) dos
[dados abertos do SP156](https://dados.prefeitura.sp.gov.br/dataset/dados-do-sp156),
da Prefeitura de São Paulo, publicados sob licença **CC0** (domínio público).
Período: 1º a 3º trimestre de 2026 (`arquivo-final-{1,2,3}-trim-26.xlsx`).

O portal informa que os dados já saem sem informações pessoais dos solicitantes.
O endereço é o do local atendido, não o de quem pediu.

## Como foi gerada

```bash
pip install python-calamine
python -I scripts/amostra_sp156.py app/database/seeders/data/sp156-amostra.csv \
  arquivo-final-1-trim-26.xlsx arquivo-final-2-trim-26.xlsx arquivo-final-3-trim-26.xlsx
```

- Aba "Solicitações"; só linhas com coordenadas dentro do município.
- Serviços do SP156 mapeados para o catálogo do app (`config/cidade.php`):

  | App | Serviço no SP156 |
  | --- | --- |
  | `via` | Tapa-buraco |
  | `residuos` | Entulho e grandes objetos em via pública; Denunciar descarte irregular de entulho |
  | `bueiro` | Solicitar limpeza de bueiros, boca de lobo e poços de visita |
  | `calcada` | Guias, sarjetas e sarjetões - solicitar manutenção |
  | `arvore` | Árvore – Solicitar avaliação em calçadas e praças para fins de poda ou remoção |
  | `veiculo` | Solicitar remoção de veículo/carcaça abandonado na via pública |

  Iluminação pública não aparece nos dados abertos do SP156 e fica sem amostra.
- Status: `Finalizada` → `resolvida`, `Aberto` → `recebida`, `Em Andamento` →
  `em_atendimento`. Canceladas ficam de fora.
- Sorteio com semente fixa (156): 100 chamados por serviço, **estratificados
  por status na proporção real de cada serviço** (método dos maiores restos) e,
  dentro de cada estrato, com prioridade para distritos diferentes.
- Limpeza: números prediais acima de 25.000 (erro de digitação na fonte) ficam
  vazios; "Superintendência das Usinas de Asfalto" (órgão, não território) não
  é usada como subprefeitura; preposições em minúsculo ("Freguesia do O").

## População × amostra

O script também gera `sp156-amostra-populacao.csv`, o retrato da população
filtrada (jan–set/2026, com coordenadas, sem canceladas):

| Serviço | Volume | Abertos (pop.) | Abertos (amostra) | Mediana (dias) | p75 (dias) | Prazo no app |
| --- | ---: | ---: | ---: | ---: | ---: | ---: |
| Entulho (`residuos`) | 121.225 | 2,3% | 2 | 1 | 2 | 5 |
| Buraco (`via`) | 83.439 | 28,2% | 28 | 7 | 19 | 7 |
| Árvore (`arvore`) | 56.020 | 21,1% | 21 | 17 | 39 | 30 |
| Veículo (`veiculo`) | 34.287 | 73,8% | 74 | 36 | 61 | 30 |
| Guias e sarjetas (`calcada`) | 24.655 | 49,1% | 49 | 21 | 51 | 20 |
| Bueiro (`bueiro`) | 18.469 | 8,5% | 9 | 1 | 5 | 7 |

"Em Andamento" praticamente não existe na fonte (3 registros), por isso não
aparece na amostra. Mediana e p75 são de dias até a finalização, só dos
finalizados.

## Limitações

- Não há descrição do cidadão: a descrição é o nome do serviço no SP156.
- Não há data de início do atendimento nem horário: a etapa "em atendimento"
  aparece como "Data não informada" e as datas são só o dia.
- O SP156 não publica prazos. O prazo (SLA) vem do catálogo fictício da Cidade
  Alfa, escolhido pela equipe olhando a distribuição real acima (sem uma regra
  única): por isso o "% no prazo" mede o serviço contra essa meta, não contra
  um prazo oficial.
- O resultado geral de `/indicadores` dá o mesmo peso (100) a cada serviço; o
  volume real é muito diferente entre eles (tabela acima).

## Data de corte

O status de cada chamado é o da data em que a planilha foi extraída: a última
finalização registrada em cada arquivo é **06/08/2026** (1º e 2º trimestres,
reextraídos juntos) e **08/10/2026** (3º trimestre). O app mede o atraso dos
chamados do SP156 nessas datas (`config/cidade.php` › `sp156_corte`), e não
hoje. Por isso esses chamados são somente leitura no app: não mudam de status
nem recebem avaliação.

## Carregar

```bash
php artisan db:seed --class=Sp156Seeder          # banco da aplicação
php artisan cidade:sp156-sql amostra.sql         # SQL para o Supabase (Postgres)
```

Os dois caminhos são idempotentes e só substituem registros com `origem = 'sp156'`.
