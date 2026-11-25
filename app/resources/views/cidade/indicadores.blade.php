@extends('cidade.layout')
@section('titulo', 'Indicadores')
@php $g = $indicadores['geral']; @endphp
@section('conteudo')
<div class="cabecalho-pagina">
    <p class="rotulo">Transparência</p>
    <h1>Os prazos estão sendo cumpridos?</h1>
    <p class="texto-grande">Cada serviço tem um prazo de atendimento. Aqui você vê quantos chamados foram resolvidos dentro dele e quanto tempo levam.</p>
</div>

<ul class="kpis" aria-label="Resumo geral">
    <li class="cartao kpi">
        <span class="kpi-rotulo">Resolvidos no prazo</span>
        <span class="kpi-numero mono">{{ $g['pct_no_prazo'] ?? '—' }}@if($g['pct_no_prazo'] !== null)<small>%</small>@endif</span>
        <span class="muted">{{ $g['resolvidas_no_prazo'] }} de {{ $g['resolvidas'] }} chamados resolvidos</span>
    </li>
    <li class="cartao kpi">
        <span class="kpi-rotulo">Tempo típico de resolução</span>
        <span class="kpi-numero mono">{{ $g['mediana_dias'] ?? '—' }}@if($g['mediana_dias'] !== null)<small>{{ $g['mediana_dias'] == 1 ? ' dia' : ' dias' }}</small>@endif</span>
        <span class="muted">Metade dos chamados resolvidos levou até esse tempo</span>
    </li>
    <li class="cartao kpi">
        <span class="kpi-rotulo">Abertos e atrasados</span>
        <span class="kpi-numero mono">{{ $g['atrasadas'] }}</span>
        <span class="muted">de {{ $g['abertas'] }} chamados abertos · SP156 na data da extração</span>
    </li>
    <li class="cartao kpi">
        <span class="kpi-rotulo">Satisfação (NPA)</span>
        <span class="kpi-numero mono">{{ $g['npa'] === null ? '—' : ($g['npa'] > 0 ? '+' : '').$g['npa'] }}</span>
        <span class="muted">{{ $g['avaliadas'] ? $g['avaliadas'].' '.($g['avaliadas'] === 1 ? 'avaliação' : 'avaliações').' de cidadãos' : 'Ainda sem avaliações' }}</span>
    </li>
</ul>

<section class="secao" aria-labelledby="por-servico">
    <h2 id="por-servico">Por serviço</h2>
    <div class="tabela-rolagem" tabindex="0" role="region" aria-labelledby="por-servico">
        <table class="tabela">
            <thead>
                <tr>
                    <th scope="col">Serviço</th>
                    <th scope="col">Prazo</th>
                    <th scope="col">Resolvidos no prazo</th>
                    <th scope="col">Tempo típico</th>
                    <th scope="col">Abertos atrasados</th>
                    <th scope="col">NPA</th>
                </tr>
            </thead>
            <tbody>
            @foreach($indicadores['servicos'] as $s)
                <tr>
                    <th scope="row"><span class="celula-servico">@include('cidade.icone', ['nome' => $s['icone'], 'tamanho' => 20]) {{ $s['nome'] }}</span></th>
                    <td class="mono">{{ $s['dias'] }} dias</td>
                    <td>@include('cidade._medidor', ['pct' => $s['pct_no_prazo']])@if($s['resolvidas'])<span class="muted pequeno">{{ $s['resolvidas_no_prazo'] }} de {{ $s['resolvidas'] }} resolvidos</span>@endif</td>
                    <td class="mono">{{ $s['mediana_dias'] === null ? '—' : $s['mediana_dias'].($s['mediana_dias'] == 1 ? ' dia' : ' dias') }}</td>
                    <td class="mono">{{ $s['atrasadas'] }} <span class="muted">de {{ $s['abertas'] }}</span></td>
                    <td class="mono">@if($s['npa'] === null)<span class="muted">—</span>@else{{ ($s['npa'] > 0 ? '+' : '').$s['npa'] }} <span class="muted pequeno">{{ $s['avaliadas'] }} {{ $s['avaliadas'] === 1 ? 'avaliação' : 'avaliações' }}</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="secao" aria-labelledby="satisfacao">
    <h2 id="satisfacao">Satisfação do cidadão</h2>
    <p class="muted">Depois que o chamado é resolvido, quem abriu pode avaliar o atendimento uma vez: Excelente, Bom ou Péssimo.</p>
    @if($g['avaliadas'])
        <ul class="distribuicao" aria-label="Distribuição das avaliações">
            @foreach(\App\Models\Demanda::AVALIACOES as $chave => $a)
                @php $n = $g['avaliacoes'][$chave]; $pct = (int) round($n / $g['avaliadas'] * 100); @endphp
                <li class="distribuicao-item distribuicao-{{ $a['tom'] }}">
                    <span class="distribuicao-rotulo">@include('cidade.icone', ['nome' => $a['icone'], 'tamanho' => 20]) {{ $a['rotulo'] }}</span>
                    <span class="medidor-trilho" aria-hidden="true"><span style="--valor: {{ $pct }}%"></span></span>
                    <span class="mono">{{ $pct }}% <span class="muted">({{ $n }})</span></span>
                </li>
            @endforeach
        </ul>
    @else
        <p class="cartao vazio-curto">Ainda não há avaliações. Elas aparecem aqui assim que cidadãos avaliarem chamados resolvidos.</p>
    @endif
    <p class="muted pequeno">NPA = % de avaliações Excelente − % de avaliações Péssimo. Vai de −100 a +100; "Bom" conta como neutro.</p>
</section>

<section class="secao nota-fonte" aria-labelledby="sobre-dados">
    <h2 id="sobre-dados">Sobre estes dados</h2>
    <p>Os indicadores juntam os chamados abertos neste protótipo e uma amostra de <strong>600 chamados reais</strong> dos
    <a href="https://dados.prefeitura.sp.gov.br/dataset/dados-do-sp156" rel="noopener">dados abertos do SP156</a>
    (Prefeitura de São Paulo, janeiro a setembro de 2026, licença CC0), sem dados pessoais.</p>
    <ul class="muted lista-notas">
        <li><strong>Prazos:</strong> a Cidade Alfa é fictícia e usa os dados de São Paulo como referência. Os prazos de cada serviço foram definidos pela equipe olhando os tempos reais do SP156, que não publica prazos oficiais.</li>
        <li><strong>Abertos e atrasados:</strong> o status dos chamados do SP156 é o da data em que cada planilha foi extraída (06/08/2026 para jan–jun e 08/10/2026 para jul–set). O atraso deles é medido nessa data, não hoje.</li>
        <li><strong>Amostra:</strong> são 100 chamados por serviço. Os números por serviço são comparáveis entre si; o resultado geral dá o mesmo peso a cada serviço e não reflete o volume real de pedidos.</li>
        <li><strong>Tempo típico:</strong> é a mediana dos chamados já resolvidos. Os que continuam abertos tendem a demorar mais e não entram nessa conta.</li>
        <li><strong>Satisfação:</strong> vem só das avaliações de quem abriu chamados neste app. Os chamados do SP156 não recebem avaliação.</li>
        <li><strong>Iluminação pública</strong> não aparece nos dados abertos do SP156, por isso não tem amostra.</li>
    </ul>
</section>
@endsection
