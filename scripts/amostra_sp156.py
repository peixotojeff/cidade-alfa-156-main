"""Gera uma amostra pequena e reprodutível dos dados abertos do SP156 para o Cidade Alfa 156+.

Uso: python -I amostra_sp156.py <saida.csv> <planilha1.xlsx> [<planilha2.xlsx> ...]

- Lê a aba de solicitações de cada planilha (dados abertos SP156, licença CC0).
- Mantém só os serviços mapeados para o catálogo do app e linhas com coordenadas.
- Descarta canceladas. Sorteia POR_SERVICO linhas por serviço com semente fixa,
  estratificando por status NA PROPORÇÃO REAL de cada serviço (maiores restos) e
  espalhando por distrito dentro de cada estrato.
- Grava também <saida>-populacao.csv com o retrato da população filtrada por
  serviço (volume, % abertos, mediana e p75 de dias até finalizar), para comparar
  a amostra com o universo.
"""
import csv, datetime as dt, random, re, statistics, sys, unicodedata
from collections import defaultdict
from python_calamine import CalamineWorkbook

POR_SERVICO = 100
SEMENTE = 156
STATUS_APP = {'Finalizada': 'resolvida', 'Aberto': 'recebida', 'Em Andamento': 'em_atendimento'}
NUMERO_MAXIMO = 25000  # numeração predial acima disso é erro de digitação na fonte


def nome_proprio(texto):
    """Title case com preposições em minúsculo: 'Freguesia Do O' -> 'Freguesia do O'."""
    t = str(texto or '').strip().title()
    return re.sub(r"(?<=\s)(De|Do|Da|Dos|Das|E|D')(?=\s|$)", lambda m: m.group(1).lower(), t)


def numero_valido(texto):
    t = str(texto or '').strip()
    return t if t.isdigit() and 0 < int(t) <= NUMERO_MAXIMO else ''


def subprefeitura_valida(texto):
    t = nome_proprio(texto)
    return '' if norm(t).startswith('superintendencia') else t  # órgão, não território


def norm(texto):
    texto = str(texto or '').replace('–', '-').replace('—', '-')  # travessões antes de remover não-ASCII
    texto = unicodedata.normalize('NFKD', texto).encode('ascii', 'ignore').decode()
    return ' '.join(texto.lower().split())


# Serviço do SP156 (início do texto, normalizado) -> código do catálogo do app.
MAPA = [
    ('tapa-buraco', 'via'),
    ('entulho e grandes objetos em via publica', 'residuos'),
    ('denunciar descarte irregular de entulho', 'residuos'),
    ('arvore - solicitar avaliacao em calcadas e pracas', 'arvore'),
    ('solicitar remocao de veiculo/carcaca abandonado', 'veiculo'),
    ('solicitar limpeza de bueiros', 'bueiro'),
    ('guias, sarjetas e sarjetoes - solicitar manutencao', 'calcada'),
]


def servico_app(servico_sp):
    s = norm(servico_sp)
    return next((codigo for prefixo, codigo in MAPA if s.startswith(prefixo)), None)


def data_iso(valor):
    if isinstance(valor, dt.datetime):
        return valor.date().isoformat()
    if isinstance(valor, dt.date):
        return valor.isoformat()
    texto = str(valor or '').strip()
    if not texto:
        return ''
    mes, dia, ano = texto.split('/')  # CSV do portal usa mês/dia/ano
    return dt.date(int(ano), int(mes), int(dia)).isoformat()


def main(saida, planilhas):
    candidatos = defaultdict(list)  # (servico, status) -> linhas
    for caminho in planilhas:
        wb = CalamineWorkbook.from_path(caminho)
        aba = next(n for n in wb.sheet_names if norm(n).startswith('solicita'))
        linhas = wb.get_sheet_by_name(aba).to_python()
        cab = [norm(c) for c in linhas[0]]
        ix = {nome: cab.index(nome) for nome in cab}
        trimestre = norm(caminho.replace('\\', '/').split('/')[-1]).replace('.xlsx', '')
        for r in linhas[1:]:
            g = lambda nome: r[ix[nome]] if ix[nome] < len(r) else ''
            codigo = servico_app(g('servico'))
            status = str(g('status')).strip()
            if not codigo or status not in STATUS_APP:
                continue
            try:
                lat, lon = float(g('latitude')), float(g('longitude'))
            except (TypeError, ValueError):
                continue
            if not (-24.1 < lat < -23.3 and -47.0 < lon < -46.3):  # dentro do município de SP
                continue
            candidatos[(codigo, status)].append({
                'servico': codigo,
                'status': STATUS_APP[status],
                'aberta_em': data_iso(g('data de abertura')),
                'finalizada_em': data_iso(g('data de finalizacao')) if status == 'Finalizada' else '',
                'servico_sp156': str(g('servico')).strip(),
                'logradouro': nome_proprio(g('logradouro')),
                'numero': numero_valido(g('numero')),
                'distrito': nome_proprio(g('distrito')),
                'subprefeitura': subprefeitura_valida(g('prefeitura operacional')),
                'latitude': f'{lat:.6f}',
                'longitude': f'{lon:.6f}',
                'trimestre': trimestre,
            })
        print(f'lido: {trimestre}', file=sys.stderr)

    rng = random.Random(SEMENTE)
    escolhidos, populacao = [], []
    for codigo in sorted({c for c, _ in candidatos}):
        pools = {s: candidatos.get((codigo, s), []) for s in STATUS_APP}
        total = sum(len(p) for p in pools.values())

        # Retrato da população filtrada deste serviço.
        dias = sorted((dt.date.fromisoformat(i['finalizada_em']) - dt.date.fromisoformat(i['aberta_em'])).days
                      for i in pools['Finalizada'] if i['finalizada_em'])
        q = statistics.quantiles(dias, n=4) if len(dias) >= 4 else [None, None, None]
        populacao.append({
            'servico': codigo, 'volume': total,
            'finalizadas': len(pools['Finalizada']), 'abertas': len(pools['Aberto']), 'em_andamento': len(pools['Em Andamento']),
            'pct_abertos': round(100 * (len(pools['Aberto']) + len(pools['Em Andamento'])) / total, 1),
            'mediana_dias': statistics.median(dias) if dias else '', 'p75_dias': q[2] if q[2] is not None else '',
        })

        # Cota proporcional ao status real (método dos maiores restos).
        exato = {s: POR_SERVICO * len(p) / total for s, p in pools.items()}
        cota = {s: int(v) for s, v in exato.items()}
        for s in sorted(exato, key=lambda s: exato[s] - cota[s], reverse=True)[:POR_SERVICO - sum(cota.values())]:
            cota[s] += 1

        lote = []
        for status, pool in pools.items():
            rng.shuffle(pool)
            # Dentro do estrato, prioriza distritos ainda não usados para espalhar pela cidade.
            vistos, primeiro, resto = set(), [], []
            for item in pool:
                (resto if item['distrito'] in vistos else primeiro).append(item)
                vistos.add(item['distrito'])
            lote += (primeiro + resto)[:cota[status]]
        escolhidos += lote
        print(f'{codigo}: {len(lote)} (cotas {cota}; população {total})', file=sys.stderr)

    with open(saida.replace('.csv', '-populacao.csv'), 'w', newline='', encoding='utf-8') as f:
        w = csv.DictWriter(f, fieldnames=list(populacao[0].keys()))
        w.writeheader()
        w.writerows(populacao)

    escolhidos.sort(key=lambda i: (i['aberta_em'], i['servico'], i['latitude']))
    with open(saida, 'w', newline='', encoding='utf-8') as f:
        w = csv.DictWriter(f, fieldnames=list(escolhidos[0].keys()))
        w.writeheader()
        w.writerows(escolhidos)
    print(f'total: {len(escolhidos)} -> {saida}', file=sys.stderr)


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2:])
