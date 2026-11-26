@extends('cidade.layout')
@section('titulo', 'Modelos de Machine Learning')
@php
    $n = fn ($v, $d = 0) => number_format((float) $v, $d, ',', '.');
    $fig = fn ($nome) => asset('portfolio/figuras/'.$nome);
    $colab = 'https://colab.research.google.com/github/'.$notebook;
    $github = 'https://github.com/'.$notebook;
    $reg = $ml['regressao'];
    $clf = $ml['classificacao'];
    $clu = $ml['clusterizacao'];
    $ano = $ml['anomalias'];
    $porNome = fn ($lista, $nome) => collect($lista)->firstWhere('modelo', $nome);
    $regBase = $porNome($reg['modelos'], 'Baseline (mediana do serviço)');
    $regMelhor = $porNome($reg['modelos'], $reg['melhor']);
    $clfMelhor = $porNome($clf['modelos'], 'Gradient Boosting');
    $clfBase = $porNome($clf['modelos'], 'Baseline (taxa do serviço)');
    $rotulosVar = ['servico' => 'serviço', 'prefeitura' => 'subprefeitura', 'mes' => 'mês', 'dia_mes' => 'dia do mês',
                   'lat' => 'latitude', 'lon' => 'longitude', 'carga_7d' => 'carga dos 7 dias', 'canal' => 'canal',
                   'dia_semana' => 'dia da semana', 'apoios_proxy' => 'repetições', 'sla_dias' => 'prazo', 'flag_fim_de_semana' => 'fim de semana'];
@endphp
@section('conteudo')
<section class="hero">
    <p class="rotulo">Portfólio · Machine Learning</p>
    <h1>Quatro técnicas, um problema real: o prazo dos chamados</h1>
    <p class="texto-grande">Regressão, classificação, clusterização e detecção de anomalias com {{ $n($ml['linhas']) }} chamados reais do SP156, preparados na Fase 4.</p>
    <div class="botoes">
        <a class="botao botao-primario" href="{{ $colab }}" target="_blank" rel="noopener">@include('cidade.icone', ['nome' => 'codigo', 'tamanho' => 20]) Abrir o notebook no Google Colab</a>
        <a class="botao botao-secundario" href="{{ $github }}" target="_blank" rel="noopener">Ver no GitHub</a>
        <a class="botao botao-secundario" href="{{ asset('portfolio/ml_sp156.ipynb') }}" download>Baixar .ipynb</a>
    </div>
</section>

<ul class="kpis" aria-label="Resumo dos resultados">
    <li class="cartao kpi">
        <span class="kpi-rotulo">Regressão</span>
        <span class="kpi-numero mono">−{{ $n($reg['ganho_mae_pct'], 0) }}<small>%</small></span>
        <span class="muted">de erro em relação à regra "mediana do serviço"</span>
    </li>
    <li class="cartao kpi">
        <span class="kpi-rotulo">Classificação</span>
        <span class="kpi-numero mono">{{ $n($clf['auc_melhor'], 2) }}</span>
        <span class="muted">ROC AUC em período futuro (jul–set)</span>
    </li>
    <li class="cartao kpi">
        <span class="kpi-rotulo">Clusterização</span>
        <span class="kpi-numero mono">{{ $clu['k'] }}<small> perfis</small></span>
        <span class="muted">de subprefeitura · {{ $clu['dbscan']['focos'] }} focos de buraco</span>
    </li>
    <li class="cartao kpi">
        <span class="kpi-rotulo">Anomalias</span>
        <span class="kpi-numero mono">{{ $ano['feriados_detectados'] }}/{{ $ano['feriados_no_periodo'] }}</span>
        <span class="muted">feriados redescobertos sem o calendário</span>
    </li>
</ul>

<section class="secao cartao principios" aria-labelledby="principios">
    <h2 id="principios">Como garantimos que os resultados são confiáveis</h2>
    <ul class="lista-notas">
        <li><strong>Sem vazamento de dados:</strong> os modelos só usam o que se sabe na abertura do chamado (serviço, local, canal, data e fila dos 7 dias anteriores), nunca o tempo real ou o status final.</li>
        <li><strong>Sempre contra um baseline:</strong> cada modelo é comparado a uma regra simples, para medir o que ele realmente acrescenta.</li>
        <li><strong>Validação temporal:</strong> na classificação, o modelo treina com jan–jun e é testado em jul–set, um período que nunca viu.</li>
        <li><strong>Erros corrigidos e documentados:</strong> viés de censura na regressão, raio do DBSCAN e escolha de k estão explicados no notebook.</li>
    </ul>
</section>

{{-- 1. Regressão --}}
<section class="secao etapa-analise" aria-labelledby="t-regressao">
    <h2 id="t-regressao"><span class="passo-numero" aria-hidden="true">1</span> Regressão: quantos dias o chamado vai levar?</h2>
    <p>Previsão do tempo de atendimento de chamados resolvidos, em escala logarítmica (a distribuição tem cauda longa). Para reduzir o viés de censura, só entram chamados com pelo menos 90 dias de observação.</p>
    <div class="tabela-rolagem" tabindex="0" role="region" aria-label="Comparação dos modelos de regressão">
        <table class="tabela">
            <thead><tr><th scope="col">Modelo</th><th scope="col">Erro médio (MAE)</th><th scope="col">Erro mediano</th><th scope="col">R² (escala log)</th></tr></thead>
            <tbody>
            @foreach($reg['modelos'] as $m)
                <tr @if($m['modelo'] === $reg['melhor']) class="linha-destaque" @endif>
                    <th scope="row">{{ $m['modelo'] }}</th>
                    <td class="mono">{{ $n($m['MAE (dias)'], 1) }} dias</td>
                    <td class="mono">{{ $n($m['Erro mediano (dias)'], 1) }} dias</td>
                    <td class="mono">{{ $n($m['R² (escala log)'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="nota">O <strong>{{ $reg['melhor'] }}</strong> erra em média <strong>{{ $n($regMelhor['MAE (dias)'], 1) }} dias</strong>, contra {{ $n($regBase['MAE (dias)'], 1) }} da regra simples.
    O que mais pesa: {{ collect(array_keys($reg['importancia']))->take(3)->map(fn ($v) => $rotulosVar[$v] ?? $v)->implode(', ') }}.</p>
    @foreach([['01_regressao.png', 'Erro de cada modelo e previsto × real', 'Barras com o erro médio de cada modelo e mapa de densidade comparando dias previstos e reais.'],
              ['02_regressao_importancia.png', 'Importância das variáveis', 'Barras com a importância por permutação de cada variável; o serviço domina, seguido da subprefeitura.']] as [$arq, $tit, $alt])
        <figure class="cartao figura-analise"><figcaption><h3>{{ $tit }}</h3></figcaption>
            <a href="{{ $fig($arq) }}" target="_blank" rel="noopener"><img src="{{ $fig($arq) }}" alt="{{ $alt }}" loading="lazy" width="1100" height="400"></a>
        </figure>
    @endforeach
</section>

{{-- 2. Classificação --}}
<section class="secao etapa-analise" aria-labelledby="t-classificacao">
    <h2 id="t-classificacao"><span class="passo-numero" aria-hidden="true">2</span> Classificação: este chamado vai estourar o prazo?</h2>
    <p>Alerta de risco no momento da abertura. Treino com {{ $n($clf['n_treino']) }} chamados de jan–jun; teste com {{ $n($clf['n_teste']) }} de jul–set ({{ $n(100 * $clf['taxa_atraso_teste'], 1) }}% atrasaram).</p>
    <div class="tabela-rolagem" tabindex="0" role="region" aria-label="Comparação dos modelos de classificação">
        <table class="tabela">
            <thead><tr><th scope="col">Modelo</th><th scope="col">ROC AUC</th><th scope="col">PR AUC</th><th scope="col">Precisão</th><th scope="col">Recall</th><th scope="col">F1</th><th scope="col">Acerto no top 10% de risco</th></tr></thead>
            <tbody>
            @foreach($clf['modelos'] as $m)
                <tr @if($m['modelo'] === 'Gradient Boosting') class="linha-destaque" @endif>
                    <th scope="row">{{ $m['modelo'] }}</th>
                    @foreach(['ROC AUC', 'PR AUC', 'Precisão', 'Recall', 'F1'] as $k)<td class="mono">{{ $n($m[$k], 3) }}</td>@endforeach
                    <td class="mono">{{ $n(100 * $m['Precisão no top 10% de risco'], 1) }}%</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="nota">Ganho <strong>modesto e honesto</strong>: AUC {{ $n($clfMelhor['ROC AUC'], 3) }} contra {{ $n($clfBase['ROC AUC'], 3) }} do baseline. A maior parte do risco já está no tipo de serviço;
    o modelo acrescenta local, fila e calendário para ordenar melhor os chamados. Entre os 10% de maior risco, <strong>{{ $n(100 * $clf['top10_melhor'], 1) }}%</strong> realmente atrasaram.</p>
    <figure class="cartao figura-analise"><figcaption><h3>Curva ROC e matriz de confusão</h3></figcaption>
        <a href="{{ $fig('03_classificacao.png') }}" target="_blank" rel="noopener"><img src="{{ $fig('03_classificacao.png') }}" alt="Curvas ROC dos três modelos e matriz de confusão do Gradient Boosting no período de teste." loading="lazy" width="1100" height="420"></a>
    </figure>
</section>

{{-- 3. Clusterização --}}
<section class="secao etapa-analise" aria-labelledby="t-cluster">
    <h2 id="t-cluster"><span class="passo-numero" aria-hidden="true">3</span> Clusterização: perfis e focos</h2>
    <p><strong>K-Means</strong> agrupou as {{ $clu['n_subprefeituras'] }} subprefeituras pelo desempenho (% no prazo, mediana de dias, % abertos), com silhueta de {{ $n($clu['silhueta'], 2) }}.</p>
    <div class="tabela-rolagem" tabindex="0" role="region" aria-label="Perfis de subprefeitura">
        <table class="tabela">
            <thead><tr><th scope="col">Perfil</th><th scope="col">Subprefeituras</th><th scope="col">No prazo</th><th scope="col">Mediana</th><th scope="col">Abertos</th><th scope="col">Membros</th></tr></thead>
            <tbody>
            @foreach($clu['grupos'] as $g)
                <tr>
                    <th scope="row">{{ $g['grupo'] }}</th>
                    <td class="mono">{{ $g['subprefeituras'] }}</td>
                    <td class="mono">{{ $n($g['% no prazo'], 1) }}%</td>
                    <td class="mono">{{ $n($g['mediana (dias)'], 1) }} d</td>
                    <td class="mono">{{ $n($g['% abertos'], 1) }}%</td>
                    <td class="texto-membros">{{ $g['membros'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="nota"><strong>DBSCAN</strong> (raio de 200 m, mínimo de 60 chamados) encontrou <strong>{{ $clu['dbscan']['focos'] }} focos</strong> de buraco na via, com {{ $n($clu['dbscan']['pct_chamados_em_focos'], 1) }}% dos chamados — candidatos a manutenção preventiva em vez de tapa-buraco repetido.</p>
    @foreach([['04_clusters_subprefeituras.png', 'Escolha de k e perfis (PCA)', 'Curva de silhueta por número de grupos e dispersão das subprefeituras nas duas componentes principais, coloridas por perfil.'],
              ['05_focos_buraco.png', 'Focos de buraco na via', 'Mapa de pontos de chamados de buraco em São Paulo com os focos detectados pelo DBSCAN destacados.']] as [$arq, $tit, $alt])
        <figure class="cartao figura-analise"><figcaption><h3>{{ $tit }}</h3></figcaption>
            <a href="{{ $fig($arq) }}" target="_blank" rel="noopener"><img src="{{ $fig($arq) }}" alt="{{ $alt }}" loading="lazy" width="1100" height="500"></a>
        </figure>
    @endforeach
</section>

{{-- 4. Anomalias --}}
<section class="secao etapa-analise" aria-labelledby="t-anomalias">
    <h2 id="t-anomalias"><span class="passo-numero" aria-hidden="true">4</span> Anomalias: dias e coordenadas fora do padrão</h2>
    <p>Volume diário comparado ao esperado para o mesmo dia da semana, com <strong>z-score robusto</strong> e <strong>Isolation Forest</strong>.
    Em {{ $ano['dias_analisados'] }} dias, {{ $ano['dias_anomalos'] }} foram marcados — incluindo <strong>{{ $ano['feriados_detectados'] }} dos {{ $ano['feriados_no_periodo'] }} feriados</strong>, sem que o calendário fosse informado.</p>
    <p>Nas coordenadas, o Isolation Forest apontou <strong>{{ $ano['pontos_suspeitos'] }} pontos suspeitos</strong> entre {{ $n($ano['pontos_analisados']) }} ({{ $n($ano['chamados_em_pontos_suspeitos']) }} chamados): muitos chamados, de vários serviços, em muitos dias — padrão típico de coordenada padrão de geocodificação.</p>
    @foreach([['06_anomalias_volume.png', 'Dias atípicos de volume', 'Linha do volume diário de chamados com o esperado e os dias anômalos marcados, vários deles feriados.'],
              ['07_anomalias_coordenadas.png', 'Coordenadas suspeitas', 'Dispersão de coordenadas por dias distintos e número de chamados, com as suspeitas destacadas.']] as [$arq, $tit, $alt])
        <figure class="cartao figura-analise"><figcaption><h3>{{ $tit }}</h3></figcaption>
            <a href="{{ $fig($arq) }}" target="_blank" rel="noopener"><img src="{{ $fig($arq) }}" alt="{{ $alt }}" loading="lazy" width="1100" height="420"></a>
        </figure>
    @endforeach
</section>

<section class="secao etapa-analise" aria-labelledby="t-valor">
    <h2 id="t-valor">O que isso muda no Cidade Alfa 156+</h2>
    <ol class="passos conclusoes">
        <li><div><strong>Prazo estimado realista</strong><p class="muted">A regressão pode mostrar ao cidadão uma previsão por local e fila, não só o prazo fixo do serviço.</p></div></li>
        <li><div><strong>Alerta de risco</strong><p class="muted">A classificação ordena a fila da secretaria pelo risco de atraso.</p></div></li>
        <li><div><strong>Gestão por perfil</strong><p class="muted">Metas e reforço de equipe por perfil de subprefeitura e rotas para os focos.</p></div></li>
        <li><div><strong>Monitoramento</strong><p class="muted">Alertas de volume atípico e auditoria da qualidade da geocodificação.</p></div></li>
    </ol>
    <p class="nota muted">Limitações: sem hora de abertura, sem protocolo e regressão restrita a chamados resolvidos. Os prazos são os do catálogo fictício da Cidade Alfa. Base: dados abertos do SP156 (CC0), scikit-learn {{ $ml['sklearn'] }}.</p>
</section>
@endsection
