@extends('cidade.layout')
@section('titulo', 'Indicadores')
@php $g = $indicadores['geral']; @endphp
@section('conteudo')
<div class="cabecalho-pagina">
    <p class="rotulo">Transparência</p>
    <h1>A prefeitura está cumprindo os prazos?</h1>
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
        <span class="muted">Metade dos chamados é resolvida em até esse tempo</span>
    </li>
    <li class="cartao kpi">
        <span class="kpi-rotulo">Abertos e atrasados</span>
        <span class="kpi-numero mono">{{ $g['atrasadas'] }}</span>
        <span class="muted">de {{ $g['abertas'] }} chamados ainda abertos</span>
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
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</section>

<section class="secao nota-fonte" aria-labelledby="sobre-dados">
    <h2 id="sobre-dados">Sobre estes dados</h2>
    <p>Os indicadores juntam os chamados abertos neste protótipo e uma amostra de <strong>600 chamados reais</strong> dos
    <a href="https://dados.prefeitura.sp.gov.br/dataset/dados-do-sp156" rel="noopener">dados abertos do SP156</a>
    (Prefeitura de São Paulo, janeiro a setembro de 2026, licença CC0), sem dados pessoais.</p>
    <p class="muted">Os prazos de cada serviço são definidos pela Cidade Alfa, uma cidade fictícia; o SP156 não publica prazos oficiais.
    "Tempo típico" é a mediana: metade dos chamados é resolvida em até esse número de dias.</p>
</section>
@endsection
