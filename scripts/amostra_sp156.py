"""Gera uma amostra pequena e reprodutível dos dados abertos do SP156 para o Cidade Alfa 156+.

Uso: python -I amostra_sp156.py <saida.csv> <planilha1.xlsx> [<planilha2.xlsx> ...]

- Lê a aba de solicitações de cada planilha (dados abertos SP156, licença CC0).
- Mantém só os serviços mapeados para o catálogo do app e linhas com coordenadas.
- Descarta canceladas. Sorteia ~POR_SERVICO linhas por serviço com semente fixa,
  espalhando por trimestre, status e distrito.
"""
import csv, datetime as dt, random, sys, unicodedata
from collections import defaultdict
from python_calamine import CalamineWorkbook

POR_SERVICO = 100
SEMENTE = 156
# Quota de status por serviço (o que faltar num status é completado pelos outros).
QUOTA = {'Finalizada': 0.5, 'Aberto': 0.35, 'Em Andamento': 0.15}
STATUS_APP = {'Finalizada': 'resolvida', 'Aberto': 'recebida', 'Em Andamento': 'em_atendimento'}


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
                'logradouro': str(g('logradouro') or '').strip().title(),
                'numero': str(g('numero') or '').strip(),
                'distrito': str(g('distrito') or '').strip().title(),
                'subprefeitura': str(g('prefeitura operacional') or '').strip().title(),
                'latitude': f'{lat:.6f}',
                'longitude': f'{lon:.6f}',
                'trimestre': trimestre,
            })
        print(f'lido: {trimestre}', file=sys.stderr)

    rng = random.Random(SEMENTE)
    escolhidos = []
    for codigo in sorted({c for c, _ in candidatos}):
        lote, faltam = [], POR_SERVICO
        for status, fracao in QUOTA.items():
            pool = candidatos.get((codigo, status), [])
            rng.shuffle(pool)
            # Prioriza distritos ainda não usados para espalhar pela cidade.
            vistos, primeiro, resto = set(), [], []
            for item in pool:
                (resto if item['distrito'] in vistos else primeiro).append(item)
                vistos.add(item['distrito'])
            quero = min(len(pool), round(POR_SERVICO * fracao))
            lote += (primeiro + resto)[:quero]
        if len(lote) < POR_SERVICO:  # completa com o status mais abundante
            extra = [i for s in QUOTA for i in candidatos.get((codigo, s), []) if i not in lote]
            lote += extra[:POR_SERVICO - len(lote)]
        escolhidos += lote
        print(f'{codigo}: {len(lote)}', file=sys.stderr)

    escolhidos.sort(key=lambda i: (i['aberta_em'], i['servico'], i['latitude']))
    with open(saida, 'w', newline='', encoding='utf-8') as f:
        w = csv.DictWriter(f, fieldnames=list(escolhidos[0].keys()))
        w.writeheader()
        w.writerows(escolhidos)
    print(f'total: {len(escolhidos)} -> {saida}', file=sys.stderr)


if __name__ == '__main__':
    main(sys.argv[1], sys.argv[2:])
