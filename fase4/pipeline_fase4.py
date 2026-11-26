"""Fase 4 — Exploração e Preparação de Dados (Cidade Alfa 156+).

Lê as planilhas de dados abertos do SP156 (jan–set/2026), mede a qualidade da base,
limpa, cria atributos de SLA e gera tabelas e gráficos da análise exploratória (EDA).

Uso (da raiz do repositório):
    fase4/.venv/Scripts/python.exe -I fase4/pipeline_fase4.py "<pasta com arquivo-final-*-trim-26.xlsx>"

Saídas em fase4/saida/:
    tabelas/*.csv      resultados agregados (versionados)
    figuras/*.png      gráficos para a apresentação (versionados)
    dados/*.parquet    base limpa e base de SLA com atributos (NÃO versionados: grandes)
    resumo.json        números-chave usados no relatório

Definições de negócio vêm do app: prazos (SLA) e datas de corte em app/config/cidade.php;
mapeamento SP156 -> serviço do app em scripts/amostra_sp156.py.
"""
import json
import re
import sys
from pathlib import Path

import matplotlib
matplotlib.use('Agg')
import matplotlib.pyplot as plt
import numpy as np
import pandas as pd

RAIZ = Path(__file__).resolve().parent.parent
sys.path.insert(0, str(RAIZ / 'scripts'))
from amostra_sp156 import NUMERO_MAXIMO, nome_proprio, servico_app  # noqa: E402

SAIDA = RAIZ / 'fase4' / 'saida'
LIMITES_SP = {'lat': (-24.1, -23.3), 'lon': (-47.0, -46.3)}  # caixa do município de São Paulo
LIMITE_BACKLOG_DIAS = 100   # aberto há mais que isso na data de corte = backlog crítico
DIAS_SEMANA = ['Seg', 'Ter', 'Qua', 'Qui', 'Sex', 'Sáb', 'Dom']

# Paleta validada (dataviz/validate_palette.js: todos os checks PASS em fundo claro).
COR = {'serie': '#2a78d6', 'texto': '#0b0b0b', 'texto2': '#52514e', 'mudo': '#898781',
       'grade': '#e1e0d9', 'eixo': '#c3c2b7', 'fundo': '#fcfcfb'}
FAIXAS = [('Atendido em 24h', '#1c5cab'), ('No prazo', '#6da7ec'),
          ('Atraso leve', '#eb6834'), ('Atraso crítico', '#a8400f')]

COLUNAS = {  # nome normalizado na planilha -> nome na base
    'data de abertura': 'data_abertura', 'data de finalizacao': 'data_finalizacao',
    'meio de comunicacao': 'canal', 'tema': 'tema', 'assunto': 'assunto', 'servico': 'servico_sp156',
    'status': 'status_original', 'orgao': 'orgao', 'logradouro': 'logradouro', 'numero': 'numero',
    'cep': 'cep', 'latitude': 'latitude', 'longitude': 'longitude',
    'prefeitura operacional': 'prefeitura_operacional', 'distrito': 'distrito',
}
# Grafia oficial das 32 subprefeituras (chave = nome normalizado na fonte). Inclui nomes antigos
# que a fonte troca no meio do ano (Perus -> Perus-Anhanguera; Casa Verde-Cachoeirinha -> ...-Limão-...).
SUBPREFEITURAS = {
    'aricanduva-formosa-carrao': 'Aricanduva-Formosa-Carrão', 'butanta': 'Butantã', 'campo limpo': 'Campo Limpo',
    'capela do socorro': 'Capela do Socorro', 'casa verde-limao-cachoeirinha': 'Casa Verde-Limão-Cachoeirinha',
    'casa verde-cachoeirinha': 'Casa Verde-Limão-Cachoeirinha', 'cidade ademar': 'Cidade Ademar',
    'cidade tiradentes': 'Cidade Tiradentes', 'ermelino matarazzo': 'Ermelino Matarazzo',
    'freguesia-brasilandia': 'Freguesia-Brasilândia', 'guaianases': 'Guaianases', 'ipiranga': 'Ipiranga',
    'itaim paulista': 'Itaim Paulista', 'itaquera': 'Itaquera', 'jabaquara': 'Jabaquara',
    'jacana-tremembe': 'Jaçanã-Tremembé', 'lapa': 'Lapa', 'm boi mirim': "M'Boi Mirim", 'mooca': 'Mooca',
    'parelheiros': 'Parelheiros', 'penha': 'Penha', 'perus': 'Perus-Anhanguera', 'perus-anhanguera': 'Perus-Anhanguera',
    'pinheiros': 'Pinheiros', 'pirituba-jaragua': 'Pirituba-Jaraguá', 'santana-tucuruvi': 'Santana-Tucuruvi',
    'santo amaro': 'Santo Amaro', 'sao mateus': 'São Mateus', 'sao miguel': 'São Miguel', 'sapopemba': 'Sapopemba',
    'se': 'Sé', 'vila maria-vila guilherme': 'Vila Maria-Vila Guilherme', 'vila mariana': 'Vila Mariana',
    'vila prudente': 'Vila Prudente',
}
# Urgência inicial por serviço (1 = baixa, 3 = alta), pelo risco à segurança de pessoas:
# árvore (queda) e buraco (acidente) altos; bueiro (alagamento) e entulho (vetores) médios.
URGENCIA = {'arvore': 3, 'via': 3, 'bueiro': 2, 'residuos': 2, 'calcada': 1, 'veiculo': 1, 'iluminacao': 2}
MESES = ['jan', 'fev', 'mar', 'abr', 'mai', 'jun', 'jul', 'ago', 'set', 'out', 'nov', 'dez']

STATUS = {'finalizada': 'finalizada', 'finalizado': 'finalizada', 'aberto': 'aberta', 'aberta': 'aberta',
          'em andamento': 'em_andamento', 'cancelada': 'cancelada', 'cancelado': 'cancelada',
          'em aprovacao': 'em_aprovacao'}


# --------------------------------------------------------------------------- configuração do app
def ler_config_app():
    php = (RAIZ / 'app' / 'config' / 'cidade.php').read_text(encoding='utf-8')
    servicos = {m[0]: {'nome': m[1], 'secretaria': m[2], 'sla_dias': int(m[3])}
                for m in re.findall(r"'(\w+)' => \['nome' => '([^']+)', 'secretaria' => '([^']+)', 'dias' => (\d+)", php)}
    cortes = {k: pd.Timestamp(v) for k, v in re.findall(r"'(\d{4}-\d)' => '(\d{4}-\d{2}-\d{2})'", php)}
    assert servicos and cortes, 'Não consegui ler serviços/cortes de app/config/cidade.php'
    return servicos, cortes


def numero(s: pd.Series) -> pd.Series:
    """Texto -> float64 (NaN quando inválido). Evita o Float64 anulável, que propaga NA em comparações."""
    return pd.to_numeric(s.astype('string').str.replace(',', '.'), errors='coerce').astype('float64')


def normalizar(s: pd.Series) -> pd.Series:
    return (s.astype('string').str.replace('–', '-').str.replace('—', '-')
            .str.normalize('NFKD').str.encode('ascii', 'ignore').str.decode('ascii')
            .str.lower().str.strip().str.replace(r'\s+', ' ', regex=True))


# --------------------------------------------------------------------------- 1. carga
def carregar(pasta: Path) -> pd.DataFrame:
    partes = []
    for arq in sorted(pasta.glob('arquivo-final-*-trim-26.xlsx')):
        df = pd.read_excel(arq, sheet_name=0, engine='calamine', dtype=object)
        df.columns = normalizar(pd.Series(df.columns)).tolist()
        df = df.rename(columns=COLUNAS)[list(COLUNAS.values())]
        df['arquivo'] = arq.stem
        partes.append(df)
        print(f'  {arq.name}: {len(df):,} linhas', file=sys.stderr)
    if not partes:
        raise SystemExit(f'Nenhuma planilha arquivo-final-*-trim-26.xlsx em {pasta}')
    return pd.concat(partes, ignore_index=True)


# --------------------------------------------------------------------------- 2. qualidade (antes)
def diagnosticar(df: pd.DataFrame) -> tuple[pd.DataFrame, dict]:
    vazio = lambda c: df[c].isna() | (df[c].astype('string').str.strip() == '')
    nulos = pd.DataFrame({'coluna': list(COLUNAS.values()),
                          'nulos_ou_vazios': [int(vazio(c).sum()) for c in COLUNAS.values()]})
    nulos['pct'] = (100 * nulos['nulos_ou_vazios'] / len(df)).round(2)

    lat = numero(df['latitude'])
    lon = numero(df['longitude'])
    tem_coord = lat.notna() & lon.notna()
    dentro = lat.between(*LIMITES_SP['lat']) & lon.between(*LIMITES_SP['lon'])
    abertura = pd.to_datetime(df['data_abertura'], errors='coerce')
    final = pd.to_datetime(df['data_finalizacao'], errors='coerce')
    status = normalizar(df['status_original']).map(STATUS).fillna('outro')

    problemas = {
        'linhas': len(df),
        'sem_data_abertura': int(abertura.isna().sum()),
        'finalizacao_antes_da_abertura': int((final < abertura).sum()),
        'finalizada_sem_data_finalizacao': int(((status == 'finalizada') & final.isna()).sum()),
        'aberta_com_data_finalizacao': int(((status == 'aberta') & final.notna()).sum()),
        'status_vazio_ou_desconhecido': int((status == 'outro').sum()),
        'sem_coordenadas': int((~tem_coord).sum()),
        'coordenadas_fora_do_municipio': int((tem_coord & ~dentro).sum()),
        'duplicatas_exatas_todas_colunas': int(df.duplicated(subset=list(COLUNAS.values())).sum()),
        'status': status.value_counts().to_dict(),
    }
    return nulos, problemas


# --------------------------------------------------------------------------- 3. limpeza
def limpar(df: pd.DataFrame) -> tuple[pd.DataFrame, list[dict]]:
    log = []
    registrar = lambda etapa, n, decisao: log.append({'etapa': etapa, 'linhas_afetadas': int(n), 'decisao': decisao})
    df = df.copy()

    df['data_abertura'] = pd.to_datetime(df['data_abertura'], errors='coerce')
    df['data_finalizacao'] = pd.to_datetime(df['data_finalizacao'], errors='coerce')
    n = df['data_abertura'].isna().sum()
    df = df[df['data_abertura'].notna()]
    registrar('Data de abertura ausente', n, 'descartado (sem abertura não há como medir prazo)')

    df['status'] = normalizar(df['status_original']).map(STATUS).fillna('outro')
    registrar('Status vazio ou desconhecido', (df['status'] == 'outro').sum(), 'mantido como "outro"; fora das métricas de SLA')

    invertida = df['data_finalizacao'] < df['data_abertura']
    registrar('Finalização antes da abertura', invertida.sum(), 'data de finalização anulada')
    df.loc[invertida, 'data_finalizacao'] = pd.NaT
    df['flag_finalizacao_sem_data'] = (df['status'] == 'finalizada') & df['data_finalizacao'].isna()
    registrar('Finalizada sem data de finalização', df['flag_finalizacao_sem_data'].sum(), 'mantido; fora do tempo de atendimento')

    lat = numero(df['latitude'])
    lon = numero(df['longitude'])
    valida = lat.between(*LIMITES_SP['lat']) & lon.between(*LIMITES_SP['lon'])
    registrar('Coordenada ausente ou fora do município', (~valida).sum(), 'coordenada anulada; registro mantido (flag_coord_valida=0)')
    df['latitude'], df['longitude'] = lat.where(valida), lon.where(valida)
    df['flag_coord_valida'] = valida.astype(int)

    num = df['numero'].astype('string').str.strip()
    num_ok = num.str.fullmatch(r'\d+', na=False) & (pd.to_numeric(num, errors='coerce').fillna(0).between(1, NUMERO_MAXIMO))
    registrar('Número predial inválido (não numérico ou > 25.000)', (num.notna() & (num != '') & ~num_ok).sum(), 'número anulado')
    df['numero'] = num.where(num_ok)

    uniq = df['distrito'].dropna().unique()
    df['distrito'] = df['distrito'].map({u: nome_proprio(u) for u in uniq})

    pref_norm = normalizar(df['prefeitura_operacional'])
    renomeada = pref_norm.isin(['perus', 'casa verde-cachoeirinha'])
    registrar('Subprefeitura com nome antigo (Perus; Casa Verde-Cachoeirinha)', renomeada.sum(),
              'unificada no nome atual (Perus-Anhanguera; Casa Verde-Limão-Cachoeirinha)')
    nao_territorio = pref_norm.notna() & ~pref_norm.isin(list(SUBPREFEITURAS))
    registrar('Prefeitura operacional que não é território (órgão ou "não encontrada")', nao_territorio.sum(),
              'anulada; contagem por valor em qualidade_prefeituras_invalidas.csv')
    pref_norm[nao_territorio].value_counts().rename('linhas').to_csv(SAIDA / 'tabelas' / 'qualidade_prefeituras_invalidas.csv')
    df['prefeitura_operacional'] = pref_norm.map(SUBPREFEITURAS)   # grafia oficial com acento; resto vira NA

    # Duplicata provável: mesmo serviço, mesmo dia e mesmo ponto (coordenada + endereço).
    # Sem número de protocolo na base aberta, linhas idênticas SEM local podem ser pedidos distintos.
    chave = ['servico_sp156', 'data_abertura', 'latitude', 'longitude', 'logradouro', 'numero']
    df['flag_duplicata_provavel'] = (df['flag_coord_valida'] == 1) & df.duplicated(subset=chave, keep='first')
    # Quantas vezes o mesmo problema foi registrado de novo: proxy de "apoios" da comunidade (RN-03).
    tamanho = df.groupby(chave, dropna=False)['servico_sp156'].transform('size')
    df['repeticoes_mesmo_ponto'] = (tamanho - 1).where(df['flag_coord_valida'] == 1, 0).astype(int)
    registrar('Duplicata provável (mesmo serviço, dia e ponto)', df['flag_duplicata_provavel'].sum(),
              'mantida na base limpa com flag; excluída das métricas de SLA')
    return df.reset_index(drop=True), log


# --------------------------------------------------------------------------- 4. atributos
def faixa_atendimento(tempo, sla, finalizada, estouro):
    if not finalizada:
        return 'Em aberto (atrasado)' if estouro > 0 else 'Em aberto (no prazo)'
    if tempo <= 1:
        return 'Atendido em 24h'
    if estouro <= 0:
        return 'No prazo'
    return 'Atraso leve' if estouro <= sla else 'Atraso crítico'   # leve = até o dobro do prazo


def criar_atributos(df: pd.DataFrame, servicos: dict, cortes: dict) -> pd.DataFrame:
    uniq = df['servico_sp156'].dropna().unique()
    df['servico_app'] = df['servico_sp156'].map({u: servico_app(u) for u in uniq})

    # Atributos de calendário (a base aberta só traz a data, sem hora).
    df['ano_mes'] = df['data_abertura'].dt.strftime('%Y-%m')
    df['dia_semana'] = df['data_abertura'].dt.day_of_week
    df['dia_mes'] = df['data_abertura'].dt.day
    df['semana_do_mes'] = (df['data_abertura'].dt.day - 1) // 7 + 1
    df['flag_fim_de_semana'] = (df['dia_semana'] >= 5).astype(int)
    trimestre = df['data_abertura'].dt.year.astype(str) + '-' + df['data_abertura'].dt.quarter.astype(str)
    df['data_corte'] = trimestre.map(cortes)

    sla = df[df['servico_app'].notna() & df['status'].isin(['finalizada', 'aberta', 'em_andamento'])
             & ~df['flag_duplicata_provavel'] & ~df['flag_finalizacao_sem_data']].copy()
    sla['sla_dias'] = sla['servico_app'].map({k: v['sla_dias'] for k, v in servicos.items()})
    sla['secretaria_app'] = sla['servico_app'].map({k: v['secretaria'] for k, v in servicos.items()})
    sla['data_limite_sla'] = sla['data_abertura'] + pd.to_timedelta(sla['sla_dias'], unit='D')
    sla['flag_finalizada'] = (sla['status'] == 'finalizada').astype(int)
    # Referência: conclusão (finalizados) ou data de corte da planilha (ainda abertos).
    sla['data_referencia'] = sla['data_finalizacao'].where(sla['flag_finalizada'] == 1, sla['data_corte'])
    sla['tempo_atendimento_dias'] = (sla['data_finalizacao'] - sla['data_abertura']).dt.days.where(sla['flag_finalizada'] == 1)
    sla['idade_na_corte_dias'] = (sla['data_corte'] - sla['data_abertura']).dt.days.where(sla['flag_finalizada'] == 0)
    sla['estouro_sla_dias'] = (sla['data_referencia'] - sla['data_limite_sla']).dt.days
    # 1 = cumpriu, 0 = atrasou; abertos ainda dentro do prazo ficam indefinidos (NaN).
    sla['flag_cumpriu_sla'] = np.select(
        [sla['flag_finalizada'].eq(1) & sla['estouro_sla_dias'].le(0), sla['estouro_sla_dias'].gt(0)], [1, 0], np.nan)
    sla['faixa_atendimento'] = [faixa_atendimento(t, s, f == 1, e) for t, s, f, e in
                                zip(sla['tempo_atendimento_dias'], sla['sla_dias'], sla['flag_finalizada'], sla['estouro_sla_dias'])]

    # Outliers de tempo (por serviço, regra de Tukey: acima de Q3 + 1,5·IQR).
    def limite_tukey(s):
        q1, q3 = s.quantile([.25, .75])
        return q3 + 1.5 * (q3 - q1)
    lim = sla.groupby('servico_app')['tempo_atendimento_dias'].transform(limite_tukey)
    sla['flag_outlier_tempo'] = (sla['tempo_atendimento_dias'] > lim).astype(int)
    sla['flag_backlog_critico'] = (sla['idade_na_corte_dias'] > LIMITE_BACKLOG_DIAS).astype(int)
    # Tempo winsorizado no p99 do serviço: para médias gerenciais sem distorção por casos extremos.
    p99 = sla.groupby('servico_app')['tempo_atendimento_dias'].transform(lambda s: s.quantile(.99))
    sla['tempo_atendimento_wins_dias'] = sla['tempo_atendimento_dias'].clip(upper=p99)
    # Score de prioridade comunitária (RN-03). A base aberta não tem apoios; usamos como proxy as
    # repetições do mesmo problema (mesmo serviço, dia e ponto). Fórmula no fase4/README.md.
    sla['urgencia_base'] = sla['servico_app'].map(URGENCIA)
    sla['apoios_proxy'] = sla['repeticoes_mesmo_ponto']
    sla['score_prioridade_comunidade'] = (sla['urgencia_base'] * (1 + np.log2(1 + sla['apoios_proxy']))).round(2)
    return sla


# --------------------------------------------------------------------------- 5. EDA
def estatisticas_tempo(sla, servicos):
    f = sla[sla['flag_finalizada'] == 1]
    g = f.groupby('servico_app')['tempo_atendimento_dias']
    t = pd.DataFrame({
        'finalizados': g.size(), 'media': g.mean().round(1), 'media_winsorizada_p99': f.groupby('servico_app')['tempo_atendimento_wins_dias'].mean().round(1),
        'mediana': g.median(), 'desvio_padrao': g.std().round(1), 'p90': g.quantile(.9), 'p99': g.quantile(.99), 'maximo': g.max(),
        'outliers_tukey': f.groupby('servico_app')['flag_outlier_tempo'].sum(),
    })
    t['sla_dias'] = t.index.map(lambda k: servicos[k]['sla_dias'])
    t['pct_no_prazo'] = (100 * f.groupby('servico_app')['flag_cumpriu_sla'].mean()).round(1)
    abertos = sla[sla['flag_finalizada'] == 0].groupby('servico_app')
    t['abertos_na_corte'] = abertos.size()
    t['pct_abertos'] = (100 * t['abertos_na_corte'] / (t['finalizados'] + t['abertos_na_corte'])).round(1)
    t['backlog_mais_100_dias'] = abertos['flag_backlog_critico'].sum()
    t.insert(0, 'servico', t.index.map(lambda k: servicos[k]['nome']))
    return t.sort_values('mediana').reset_index()


def estilo():
    plt.rcParams.update({
        'figure.facecolor': COR['fundo'], 'axes.facecolor': COR['fundo'], 'savefig.facecolor': COR['fundo'],
        'font.size': 12, 'axes.titlesize': 15, 'axes.titleweight': 'bold', 'axes.titlelocation': 'left',
        'axes.titlecolor': COR['texto'], 'axes.labelcolor': COR['texto2'], 'text.color': COR['texto'],
        'xtick.color': COR['texto2'], 'ytick.color': COR['texto'], 'axes.edgecolor': COR['eixo'],
        'axes.spines.top': False, 'axes.spines.right': False, 'axes.grid': True, 'grid.color': COR['grade'],
        'grid.linewidth': .8, 'axes.axisbelow': True, 'legend.frameon': False,
    })


def br(texto: str) -> str:
    """Formato numérico brasileiro: 52,142.5 -> 52.142,5."""
    return texto.translate(str.maketrans(',.', '.,'))


MILHAR = matplotlib.ticker.FuncFormatter(lambda v, _: br(f'{v:,.0f}'))


def salvar(fig, nome, fonte='Fonte: dados abertos SP156 (Prefeitura de São Paulo, CC0), jan–set/2026.'):
    fig.text(0.01, -0.02, fonte, fontsize=9, color=COR['mudo'], va='top')   # abaixo do rótulo do eixo
    fig.savefig(SAIDA / 'figuras' / nome, dpi=200, bbox_inches='tight')
    plt.close(fig)


def fig_tempo(sla, servicos, tabela):
    ordem = tabela['servico_app'].tolist()
    f = sla[sla['flag_finalizada'] == 1]
    dados = [f.loc[f['servico_app'] == s, 'tempo_atendimento_dias'].clip(lower=0.5) for s in ordem]
    fig, ax = plt.subplots(figsize=(11, 5.8))
    ax.boxplot(dados, orientation='horizontal', widths=.5, patch_artist=True, showfliers=True,
               boxprops={'facecolor': '#cde2fb', 'edgecolor': COR['serie'], 'linewidth': 1.2},
               medianprops={'color': '#104281', 'linewidth': 2.4}, whiskerprops={'color': COR['serie']},
               capprops={'color': COR['serie']},
               flierprops={'marker': 'o', 'markersize': 2, 'markerfacecolor': COR['mudo'], 'markeredgecolor': 'none', 'alpha': .25})
    for i, s in enumerate(ordem, start=1):
        ax.plot(servicos[s]['sla_dias'], i, marker='D', markersize=9, color='#a8400f', markeredgecolor=COR['fundo'], zorder=5)
    ax.set_xscale('log')
    ax.set_xticks([0.5, 1, 2, 5, 10, 30, 60, 100, 200], ['mesmo\ndia', '1', '2', '5', '10', '30', '60', '100', '200'])
    ax.xaxis.set_minor_locator(matplotlib.ticker.NullLocator())
    ax.set_yticks(range(1, len(ordem) + 1), [servicos[s]['nome'] for s in ordem])
    ax.set_xlabel('Dias da abertura à finalização (escala logarítmica)')
    ax.grid(axis='y', visible=False)
    ax.plot([], [], marker='D', linestyle='none', color='#a8400f', label='Prazo (SLA) da Cidade Alfa')
    ax.plot([], [], color='#104281', linewidth=2.4, label='Mediana')
    ax.legend(loc='lower right')
    ax.set_title('Tempo de atendimento por serviço: a mediana fica longe do pior caso')
    salvar(fig, '01_tempo_atendimento_por_servico.png')


def fig_faixas(sla, servicos, ordem):
    f = sla[sla['flag_finalizada'] == 1]
    tab = (pd.crosstab(f['servico_app'], f['faixa_atendimento'], normalize='index') * 100).reindex(ordem)
    fig, ax = plt.subplots(figsize=(11, 5.2))
    esquerda = np.zeros(len(tab))
    for nome, cor in FAIXAS:
        v = tab.get(nome, pd.Series(0, index=tab.index)).to_numpy()
        ax.barh(range(len(tab)), v, left=esquerda, color=cor, height=.62, label=nome, edgecolor=COR['fundo'], linewidth=2)
        for i, (x, w) in enumerate(zip(esquerda, v)):
            if w >= 7:
                ax.text(x + w / 2, i, f'{w:.0f}%', ha='center', va='center', fontsize=11,
                        color='#ffffff' if cor in ('#1c5cab', '#a8400f', '#eb6834') else COR['texto'])
        esquerda += v
    ax.set_yticks(range(len(tab)), [servicos[s]['nome'] for s in tab.index])
    ax.invert_yaxis()
    ax.set_xlim(0, 100)
    ax.set_xlabel('% dos chamados finalizados')
    ax.grid(axis='y', visible=False)
    ax.legend(ncols=4, loc='lower left', bbox_to_anchor=(0, 1.0), fontsize=11)
    ax.set_title('Faixas de atendimento dos chamados finalizados', pad=34)
    salvar(fig, '02_faixas_de_atendimento.png')


def fig_barras(serie, titulo, nome, rotulo_x, formato='{:,.0f}', cor=None):
    serie = serie.iloc[::-1]
    fig, ax = plt.subplots(figsize=(11, max(4.5, .38 * len(serie) + 1.2)))
    ax.barh(range(len(serie)), serie.to_numpy(), color=cor or COR['serie'], height=.62)
    for i, v in enumerate(serie.to_numpy()):
        ax.text(v, i, ' ' + br(formato.format(v)), va='center', fontsize=10.5, color=COR['texto2'])
    ax.set_yticks(range(len(serie)), serie.index)
    ax.xaxis.set_major_formatter(MILHAR)
    ax.set_xlabel(rotulo_x)
    ax.grid(axis='y', visible=False)
    ax.margins(x=.12)
    ax.set_title(titulo)
    salvar(fig, nome)


def fig_semana(base):
    vol = base.groupby('dia_semana').size() / base.groupby('dia_semana')['data_abertura'].nunique()
    fig, ax = plt.subplots(figsize=(11, 4.8))
    ax.bar(range(7), vol.reindex(range(7)).to_numpy(), color=COR['serie'], width=.6)
    for i, v in enumerate(vol.reindex(range(7)).to_numpy()):
        ax.text(i, v, br(f'{v:,.0f}'), ha='center', va='bottom', fontsize=10.5, color=COR['texto2'])
    ax.set_xticks(range(7), DIAS_SEMANA)
    ax.yaxis.set_major_formatter(MILHAR)
    ax.set_ylabel('Chamados por dia (média)')
    ax.grid(axis='x', visible=False)
    ax.set_title('Volume médio diário por dia da semana: queda forte no fim de semana')
    salvar(fig, '05_volume_por_dia_da_semana.png')


def fig_tendencia(base):
    semanal = base.set_index('data_abertura').resample('W-MON', label='left', closed='left').size()
    semanal = semanal.iloc[1:-1]  # descarta semanas parciais das pontas
    fig, ax = plt.subplots(figsize=(11, 4.8))
    ax.plot(semanal.index, semanal.to_numpy(), color=COR['serie'], linewidth=2)
    pico = semanal.idxmax()
    ax.annotate(br(f'pico: {semanal.max():,.0f}') + f' (semana de {pico.day:02d}/{pico.month:02d})', (pico, semanal.max()), xytext=(10, -4),
                textcoords='offset points', fontsize=10.5, color=COR['texto2'], va='top')
    ax.set_ylabel('Chamados por semana')
    meses = pd.date_range(semanal.index.min().normalize().replace(day=1), semanal.index.max(), freq='MS')
    ax.set_xticks(meses, [MESES[m.month - 1] for m in meses])
    ax.yaxis.set_major_formatter(MILHAR)
    ax.set_ylim(0, semanal.max() * 1.12)
    ax.grid(axis='x', visible=False)
    ax.set_title('Chamados por semana, jan–set/2026 (todos os serviços)')
    salvar(fig, '06_tendencia_semanal.png')


def fig_dia_mes(base):
    vol = base.groupby('dia_mes').size() / base.groupby('dia_mes')['data_abertura'].nunique()
    fig, ax = plt.subplots(figsize=(11, 4.6))
    ax.plot(vol.index, vol.to_numpy(), color=COR['serie'], linewidth=2, marker='o', markersize=4)
    ax.set_xticks([1, 5, 10, 15, 20, 25, 31])
    ax.yaxis.set_major_formatter(MILHAR)
    ax.set_xlabel('Dia do mês')
    ax.set_ylabel('Chamados por dia (média)')
    ax.set_ylim(bottom=0)
    ax.set_title('Volume médio por dia do mês')
    salvar(fig, '07_volume_por_dia_do_mes.png')


# --------------------------------------------------------------------------- exportação para o app
def exportar_para_app(resumo, tempo, dup, pref_prazo, sla):
    """Copia as figuras e grava um JSON pequeno que a página /analise do app lê (app/public/fase4)."""
    import shutil
    destino = RAIZ / 'app' / 'public' / 'fase4'
    (destino / 'figuras').mkdir(parents=True, exist_ok=True)
    for png in (SAIDA / 'figuras').glob('*.png'):
        shutil.copy2(png, destino / 'figuras' / png.name)
    q = resumo['qualidade']
    dados = {
        'gerado_em': pd.Timestamp.now().strftime('%Y-%m-%d'),
        'linhas_brutas': resumo['linhas_brutas'], 'linhas_base_sla': resumo['linhas_base_sla'],
        'arquivos': 3, 'periodo': 'jan–set/2026',
        'qualidade': {k: q[k] for k in ['sem_coordenadas', 'coordenadas_fora_do_municipio', 'duplicatas_exatas_todas_colunas',
                                        'finalizada_sem_data_finalizacao', 'sem_data_abertura', 'finalizacao_antes_da_abertura']},
        'status': q['status'],
        'decisoes_limpeza': [d for d in resumo['decisoes_limpeza'] if d['linhas_afetadas'] > 0],
        'sla_geral': resumo['sla_geral'],
        'outliers_tempo': int(sla['flag_outlier_tempo'].sum()),
        'com_repeticao_pct': round(100 * (sla['apoios_proxy'] > 0).mean(), 1),
        'max_repeticoes': int(sla['apoios_proxy'].max()),
        'tempo_por_servico': tempo[['servico', 'finalizados', 'media', 'mediana', 'desvio_padrao', 'p90', 'sla_dias',
                                    'pct_no_prazo', 'pct_abertos']].to_dict('records'),
        'duplicatas_por_servico': dup.reset_index().sort_values('pct_duplicatas', ascending=False).to_dict('records'),
        'prazo_prefeitura': {'melhor': pref_prazo['pct_no_prazo'].idxmax(), 'melhor_pct': float(pref_prazo['pct_no_prazo'].max()),
                             'pior': pref_prazo['pct_no_prazo'].idxmin(), 'pior_pct': float(pref_prazo['pct_no_prazo'].min())},
        'fim_de_semana_pct': resumo['fim_de_semana_pct'],
        'canais_pct': resumo['canais_pct'],
        'top_prefeituras': resumo['top_prefeituras'],
    }
    (destino / 'eda.json').write_text(json.dumps(dados, ensure_ascii=False, indent=1, default=str), encoding='utf-8')


# --------------------------------------------------------------------------- principal
def main(pasta):
    servicos, cortes = ler_config_app()
    for d in ['tabelas', 'figuras', 'dados']:
        (SAIDA / d).mkdir(parents=True, exist_ok=True)
    estilo()

    print('1/5 Carregando planilhas…', file=sys.stderr)
    cache = SAIDA / 'dados' / 'sp156_bruto.parquet'   # cópia fiel das planilhas (camada "raw")
    if cache.exists():
        bruto = pd.read_parquet(cache)
        print(f'  usando cache {cache.name}: {len(bruto):,} linhas', file=sys.stderr)
    else:
        bruto = carregar(Path(pasta))
        bruto.astype('string').to_parquet(cache, index=False)
        bruto = pd.read_parquet(cache)

    print('2/5 Diagnóstico de qualidade…', file=sys.stderr)
    nulos, problemas = diagnosticar(bruto)
    nulos.to_csv(SAIDA / 'tabelas' / 'qualidade_nulos_por_coluna.csv', index=False)

    print('3/5 Limpeza…', file=sys.stderr)
    limpo, log = limpar(bruto)
    pd.DataFrame(log).to_csv(SAIDA / 'tabelas' / 'limpeza_decisoes.csv', index=False)

    print('4/5 Engenharia de atributos…', file=sys.stderr)
    sla = criar_atributos(limpo, servicos, cortes)
    limpo.drop(columns=['status_original']).to_parquet(SAIDA / 'dados' / 'sp156_limpo.parquet', index=False)
    sla.drop(columns=['status_original']).to_parquet(SAIDA / 'dados' / 'sp156_sla_atributos.parquet', index=False)

    print('5/5 Análise exploratória…', file=sys.stderr)
    tempo = estatisticas_tempo(sla, servicos)
    tempo.to_csv(SAIDA / 'tabelas' / 'eda_tempo_por_servico.csv', index=False)
    ordem = tempo['servico_app'].tolist()

    base = limpo[limpo['status'] != 'cancelada']
    canal = (100 * base['canal'].fillna('(vazio)').value_counts(normalize=True)).round(1)
    canal.rename('pct').to_csv(SAIDA / 'tabelas' / 'eda_canais.csv')
    canal_servico = (pd.crosstab(sla['servico_app'].map(lambda k: servicos[k]['nome']), sla['canal'].fillna('(vazio)'),
                                 normalize='index') * 100).round(1)
    canal_servico.to_csv(SAIDA / 'tabelas' / 'eda_canais_por_servico.csv')

    pref = base['prefeitura_operacional'].value_counts()
    pref.rename('chamados').to_csv(SAIDA / 'tabelas' / 'eda_volume_por_prefeitura.csv')
    dist = base['distrito'].value_counts()
    dist.rename('chamados').to_csv(SAIDA / 'tabelas' / 'eda_volume_por_distrito.csv')
    fin = sla[sla['flag_finalizada'] == 1]
    pref_prazo = fin.groupby('prefeitura_operacional').agg(finalizados=('flag_cumpriu_sla', 'size'),
                                                           pct_no_prazo=('flag_cumpriu_sla', 'mean'),
                                                           mediana_dias=('tempo_atendimento_dias', 'median'))
    pref_prazo = pref_prazo[pref_prazo['finalizados'] >= 300]
    pref_prazo['pct_no_prazo'] = (100 * pref_prazo['pct_no_prazo']).round(1)
    pref_prazo.sort_values('pct_no_prazo').to_csv(SAIDA / 'tabelas' / 'eda_prazo_por_prefeitura.csv')
    tema = base['tema'].value_counts().head(15)
    tema.rename('chamados').to_csv(SAIDA / 'tabelas' / 'eda_top_temas.csv')
    sem = base.groupby('dia_semana').size()
    sem.index = [DIAS_SEMANA[i] for i in sem.index]
    sem.rename('chamados').to_csv(SAIDA / 'tabelas' / 'eda_volume_por_dia_da_semana.csv')
    faixas = (pd.crosstab(sla['servico_app'].map(lambda k: servicos[k]['nome']), sla['faixa_atendimento']))
    faixas.to_csv(SAIDA / 'tabelas' / 'eda_faixas_por_servico.csv')

    fig_tempo(sla, servicos, tempo)
    fig_faixas(sla, servicos, ordem)
    fig_barras(pref.head(15), 'Prefeituras operacionais com mais chamados', '03_volume_por_prefeitura.png', 'Chamados (jan–set/2026)')
    top = pref_prazo.sort_values('pct_no_prazo')
    fig_barras(pd.concat([top.head(8), top.tail(8)])['pct_no_prazo'], 'Chamados resolvidos no prazo, por prefeitura (8 piores e 8 melhores)',
               '04_prazo_por_prefeitura.png', '% dos finalizados dentro do prazo (6 serviços do app)', '{:.0f}%')
    fig_semana(base)
    fig_tendencia(base)
    fig_dia_mes(base)
    fig_barras(canal.head(8), 'Canal de abertura dos chamados', '08_canais.png', '% dos chamados', '{:.1f}%')

    # Reclamações repetidas do mesmo problema: base para o apoio comunitário (RN-03).
    app = limpo[limpo['servico_sp156'].map(lambda s: servico_app(s) if isinstance(s, str) else None).notna()].copy()
    app['servico'] = app['servico_sp156'].map(lambda s: servicos[servico_app(s)]['nome'])
    dup = app.groupby('servico').agg(chamados=('flag_duplicata_provavel', 'size'), duplicatas=('flag_duplicata_provavel', 'sum'))
    dup['pct_duplicatas'] = (100 * dup['duplicatas'] / dup['chamados']).round(1)
    dup.sort_values('pct_duplicatas', ascending=False).to_csv(SAIDA / 'tabelas' / 'eda_duplicatas_por_servico.csv')
    fig_barras(dup['pct_duplicatas'].sort_values(ascending=False),
               'Mesmo problema registrado mais de uma vez (mesmo dia e mesmo ponto)', '09_duplicatas_por_servico.png',
               '% dos chamados que repetem outro já aberto', '{:.1f}%')

    resumo = {
        'linhas_brutas': problemas['linhas'], 'linhas_limpas': len(limpo), 'linhas_base_sla': len(sla),
        'qualidade': problemas, 'decisoes_limpeza': log,
        'sla_geral': {
            'finalizados': int(fin.shape[0]), 'pct_no_prazo': round(100 * fin['flag_cumpriu_sla'].mean(), 1),
            'mediana_dias': float(fin['tempo_atendimento_dias'].median()),
            'abertos_na_corte': int((sla['flag_finalizada'] == 0).sum()),
            'abertos_atrasados_na_corte': int(((sla['flag_finalizada'] == 0) & (sla['flag_cumpriu_sla'] == 0)).sum()),
            'backlog_mais_100_dias': int(sla['flag_backlog_critico'].sum()),
        },
        'fim_de_semana_pct': round(100 * base['flag_fim_de_semana'].mean(), 1),
        'canais_pct': canal.to_dict(),
        'top_prefeituras': pref.head(5).to_dict(),
    }
    (SAIDA / 'resumo.json').write_text(json.dumps(resumo, ensure_ascii=False, indent=2, default=str), encoding='utf-8')
    exportar_para_app(resumo, tempo, dup, pref_prazo, sla)
    print(json.dumps(resumo['sla_geral'], ensure_ascii=False), file=sys.stderr)
    print('Pronto:', SAIDA, file=sys.stderr)


if __name__ == '__main__':
    if len(sys.argv) != 2:
        raise SystemExit(__doc__)
    main(sys.argv[1])
