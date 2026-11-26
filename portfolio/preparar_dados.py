"""Prepara a base compacta do portfólio de Machine Learning a partir das camadas da Fase 4.

Uso (da raiz): fase4/.venv/Scripts/python -I portfolio/preparar_dados.py
Requer fase4/saida/dados/sp156_sla_atributos.parquet e sp156_limpo.parquet (gerados por fase4/pipeline_fase4.py).

Saídas (versionadas, pequenas, usadas pelo notebook no Colab):
  portfolio/dados/sp156_modelagem.parquet  um chamado por linha, só colunas úteis aos modelos
  portfolio/dados/volume_diario.csv         chamados abertos por dia (total e por serviço)

Privacidade: sem logradouro/número/CEP; coordenadas arredondadas a 3 casas (~100 m),
como o ETL de fronteira da arquitetura da Fase 3.
"""
from pathlib import Path

import pandas as pd

RAIZ = Path(__file__).resolve().parent.parent
ORIGEM = RAIZ / 'fase4' / 'saida' / 'dados'
DESTINO = RAIZ / 'portfolio' / 'dados'
DESTINO.mkdir(parents=True, exist_ok=True)

ouro = pd.read_parquet(ORIGEM / 'sp156_sla_atributos.parquet')
limpo = pd.read_parquet(ORIGEM / 'sp156_limpo.parquet',
                        columns=['data_abertura', 'status', 'servico_app', 'prefeitura_operacional'])

# ---------------------------------------------------------------- carga dos 7 dias anteriores
# Todos os registros dos 6 serviços (inclusive repetidos) medem a demanda que chegou à equipe.
# Só usa dias ANTERIORES à abertura: não vaza informação do futuro.
dem = limpo[limpo['servico_app'].notna() & limpo['prefeitura_operacional'].notna()]
diario = (dem.groupby(['servico_app', 'prefeitura_operacional', 'data_abertura']).size()
          .rename('n').reset_index())
carga = []
for (serv, pref), g in diario.groupby(['servico_app', 'prefeitura_operacional']):
    s = g.set_index('data_abertura')['n'].asfreq('D', fill_value=0)
    c = s.rolling(7, min_periods=1).sum().shift(1).fillna(0)        # 7 dias antes, sem o próprio dia
    carga.append(pd.DataFrame({'servico_app': serv, 'prefeitura_operacional': pref,
                               'data_abertura': c.index, 'carga_7d': c.to_numpy().astype(int)}))
carga = pd.concat(carga, ignore_index=True)

m = ouro.merge(carga, on=['servico_app', 'prefeitura_operacional', 'data_abertura'], how='left')
m['carga_7d'] = m['carga_7d'].fillna(0).astype(int)

base = pd.DataFrame({
    'data_abertura': m['data_abertura'],
    'mes': m['data_abertura'].dt.month.astype('int8'),
    'dia_semana': m['dia_semana'].astype('int8'),
    'dia_mes': m['dia_mes'].astype('int8'),
    'semana_do_mes': m['semana_do_mes'].astype('int8'),
    'flag_fim_de_semana': m['flag_fim_de_semana'].astype('int8'),
    'servico': m['servico_app'].astype('category'),
    'prefeitura': m['prefeitura_operacional'].astype('category'),
    'canal': m['canal'].fillna('(vazio)').astype('category'),
    'lat': m['latitude'].round(3),
    'lon': m['longitude'].round(3),
    'apoios_proxy': m['apoios_proxy'].astype('int16'),
    'carga_7d': m['carga_7d'].astype('int32'),
    'sla_dias': m['sla_dias'].astype('int16'),
    # Alvos e informação do desfecho (NUNCA usar como entrada dos modelos)
    'status': m['status'].astype('category'),
    'flag_finalizada': m['flag_finalizada'].astype('int8'),
    'tempo_atendimento_dias': m['tempo_atendimento_dias'],
    'estouro_sla_dias': m['estouro_sla_dias'],
    'flag_cumpriu_sla': m['flag_cumpriu_sla'],
    'faixa_atendimento': m['faixa_atendimento'].astype('category'),
    'data_corte': m['data_corte'],
})
base.to_parquet(DESTINO / 'sp156_modelagem.parquet', index=False, compression='zstd')

# ---------------------------------------------------------------- volume diário (anomalias)
ativos = limpo[limpo['status'] != 'cancelada']
vol = ativos.groupby('data_abertura').size().rename('total').to_frame()
por_servico = ativos[ativos['servico_app'].notna()].groupby(['data_abertura', 'servico_app']).size().unstack(fill_value=0)
vol = vol.join(por_servico).fillna(0).astype(int)
vol.index.name = 'data'
vol.to_csv(DESTINO / 'volume_diario.csv')

print(f"sp156_modelagem.parquet: {len(base):,} linhas, {(DESTINO / 'sp156_modelagem.parquet').stat().st_size / 1e6:.1f} MB")
print(f"volume_diario.csv: {len(vol)} dias, colunas {list(vol.columns)}")
