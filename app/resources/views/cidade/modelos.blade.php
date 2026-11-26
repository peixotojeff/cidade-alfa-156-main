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
    $rotulosVar = ['servico' => 'tipo de serviço', 'prefeitura' => 'subprefeitura', 'mes' => 'mês', 'dia_mes' => 'dia do mês',
                   'lat' => 'localização', 'lon' => 'localização', 'carga_7d' => 'fila dos 7 dias', 'canal' => 'canal',
                   'dia_semana' => 'dia da semana', 'apoios_proxy' => 'repetições', 'sla_dias' => 'prazo', 'flag_fim_de_semana' => 'fim de semana'];
    $topVars = collect(array_keys($reg['importancia']))->map(fn ($v) => $rotulosVar[$v] ?? $v)->unique()->take(2)->values();
    $taxaGeral = 100 * $clf['taxa_atraso_teste'];
    $grupos = collect($clu['grupos']);
    $lentas = $grupos->last();
    $razaoLentas = $lentas['mediana (dias)'] / max(0.1, $grupos->slice(0, -1)->avg('mediana (dias)'));
    // Termo do glossário: link sublinhado pontilhado que leva à definição
    $termo = fn ($id, $texto) => '<a class="termo" href="#g-'.$id.'">'.e($texto).'</a>';
@endphp
@section('conteudo')
<section class="hero">
    <p class="rotulo">Portfólio · Machine Learning</p>
    <h1>Quatro técnicas, um problema real: o prazo dos chamados</h1>
    <p class="texto-grande">Usamos {{ $n($ml['linhas']) }} chamados reais do SP156 para ensinar o computador a prever, alertar, agrupar e vigiar. Esta página explica os resultados sem exigir conhecimento de estatística ou programação.</p>
    <div class="botoes">
        <a class="botao botao-primario" href="{{ $colab }}" target="_blank" rel="noopener">@include('cidade.icone', ['nome' => 'codigo', 'tamanho' => 20]) Abrir o notebook no Google Colab</a>
        <a class="botao botao-secundario" href="{{ $github }}" target="_blank" rel="noopener">Ver no GitHub</a>
        <a class="botao botao-secundario" href="#glossario">Glossário dos termos</a>
    </div>
</section>

{{-- Resumo para gestores --}}
<section class="cartao resumo-gestor" aria-labelledby="resumo-gestor">
    <h2 id="resumo-gestor">Resumo para quem tem 1 minuto</h2>
    <ul class="resumo-frases">
        <li>
            <span class="resumo-frase-titulo">@include('cidade.icone', ['nome' => 'relogio', 'tamanho' => 20]) Regressão: prever o tempo</span>
            <p>Conseguimos estimar <strong>quantos dias um chamado vai levar</strong>. A previsão erra em média {{ $n($regMelhor['MAE (dias)'], 0) }} dias, <strong>{{ $n($reg['ganho_mae_pct'], 0) }}% menos</strong> que a regra de bolso "cada serviço leva o tempo típico dele".</p>
        </li>
        <li>
            <span class="resumo-frase-titulo">@include('cidade.icone', ['nome' => 'alerta', 'tamanho' => 20]) Classificação: alertar atrasos</span>
            <p>Na abertura, cada chamado recebe uma nota de risco. Olhando primeiro os 10% mais arriscados, <strong>{{ $n(100 * $clf['top10_melhor'], 0) }} em cada 100 realmente atrasariam</strong>, contra {{ $n($taxaGeral, 0) }} em 100 atendendo por ordem de chegada.</p>
        </li>
        <li>
            <span class="resumo-frase-titulo">@include('cidade.icone', ['nome' => 'camadas', 'tamanho' => 20]) Clusterização: agrupar parecidos</span>
            <p>As subprefeituras formam <strong>{{ $clu['k'] }} perfis</strong> de atendimento; {{ $lentas['subprefeituras'] }} delas são "{{ $lentas['grupo'] }}". E <strong>{{ $clu['dbscan']['focos'] }} pontos da cidade</strong> concentram reclamações de buraco.</p>
        </li>
        <li>
            <span class="resumo-frase-titulo">@include('cidade.icone', ['nome' => 'lupa', 'tamanho' => 20]) Anomalias: vigiar o incomum</span>
            <p>O sistema detecta sozinho dias fora do padrão: achou <strong>{{ $ano['feriados_detectados'] }} dos {{ $ano['feriados_no_periodo'] }} feriados</strong> sem conhecer o calendário. Também aponta {{ $ano['pontos_suspeitos'] }} endereços provavelmente mal localizados no mapa.</p>
        </li>
    </ul>
</section>

<section class="secao cartao principios" aria-labelledby="principios">
    <h2 id="principios">Por que confiar nesses números</h2>
    <ul class="lista-notas">
        <li><strong>O modelo não "cola na prova".</strong> Ele só usa o que se sabe no momento em que o chamado é aberto (serviço, local, canal, data). Nunca usa o desfecho. Isso evita o {!! $termo('vazamento', 'vazamento de dados') !!}.</li>
        <li><strong>Sempre comparamos com uma regra de bolso</strong> (o {!! $termo('baseline', 'baseline') !!}), para medir o que o modelo realmente acrescenta.</li>
        <li><strong>Testamos no "futuro".</strong> No alerta de atraso, o modelo aprendeu com jan–jun e foi testado em jul–set, que nunca tinha visto ({!! $termo('validacao-temporal', 'validação temporal') !!}).</li>
        <li><strong>Mostramos os erros que corrigimos</strong>, em vez de escondê-los. Estão explicados no notebook.</li>
    </ul>
</section>

{{-- 1. Regressão --}}
<section class="secao etapa-analise" aria-labelledby="t-regressao">
    <h2 id="t-regressao"><span class="passo-numero" aria-hidden="true">1</span> Regressão: quantos dias o chamado vai levar?</h2>
    <div class="em-uma-frase">
        <strong>Em uma frase:</strong> a previsão fica, em média, a <strong>{{ $n($regMelhor['MAE (dias)'], 0) }} dias</strong> do tempo real, contra {{ $n($regBase['MAE (dias)'], 0) }} dias da regra de bolso. O que mais influencia: {!! $topVars->map(fn ($v) => '<strong>'.e($v).'</strong>')->implode(' e ') !!}.
    </div>
    <p>{!! $termo('regressao', 'Regressão') !!} é a técnica que prevê <strong>um número</strong>, aqui o número de dias até a resolução. Comparamos três formas de prever:</p>
    <ul class="lista-notas">
        <li><strong>Regra de bolso</strong> ({!! $termo('baseline', 'baseline') !!}): "cada serviço leva o tempo típico dele" (por exemplo, buraco leva 7 dias).</li>
        <li><strong>{!! $termo('linear', 'Regressão Linear') !!}</strong>: um modelo clássico, que soma o peso de cada fator.</li>
        <li><strong>{!! $termo('boosting', 'Gradient Boosting') !!}</strong>: muitas pequenas árvores de decisão, cada uma corrigindo os erros da anterior.</li>
    </ul>
    <div class="tabela-rolagem" tabindex="0" role="region" aria-label="Comparação dos modelos de regressão">
        <table class="tabela">
            <thead><tr><th scope="col">Forma de prever</th><th scope="col">Erro médio<br><span class="cabecalho-ajuda">{!! $termo('mae', 'MAE') !!} · menor é melhor</span></th><th scope="col">Erro típico<br><span class="cabecalho-ajuda">{!! $termo('mediana', 'mediano') !!} · menor é melhor</span></th><th scope="col">Quanto explica<br><span class="cabecalho-ajuda">{!! $termo('r2', 'R²') !!} · de 0 a 1, maior é melhor</span></th></tr></thead>
            <tbody>
            @foreach($reg['modelos'] as $m)
                <tr @if($m['modelo'] === $reg['melhor']) class="linha-destaque" @endif>
                    <th scope="row">{{ $m['modelo'] }}@if($m['modelo'] === $reg['melhor']) <span class="selo-melhor">melhor</span>@endif</th>
                    <td class="mono">{{ $n($m['MAE (dias)'], 1) }} dias</td>
                    <td class="mono">{{ $n($m['Erro mediano (dias)'], 1) }} dias</td>
                    <td class="mono">{{ $n($m['R² (escala log)'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="como-ler"><strong>Como ler:</strong> "erro médio de {{ $n($regMelhor['MAE (dias)'], 1) }} dias" quer dizer que, se o modelo prevê 20 dias, o tempo real costuma ficar a cerca de {{ $n($regMelhor['MAE (dias)'], 0) }} dias disso, para mais ou para menos.
    O erro típico é bem menor ({{ $n($regMelhor['Erro mediano (dias)'], 1) }} dias) porque a maioria dos chamados é curta e fácil de prever; poucos casos muito demorados puxam a média para cima.</p>

    <figure class="cartao figura-analise">
        <figcaption>
            <h3>Erro de cada modelo e previsto × real</h3>
            <p class="como-ler"><strong>Como ler:</strong> à esquerda, barras menores são melhores. À direita, cada mancha azul junta chamados: quanto mais escura, mais chamados. Se o modelo fosse perfeito, tudo cairia sobre a linha vermelha. Os eixos usam {!! $termo('log', 'escala logarítmica') !!}, para caber 1 dia e 200 dias no mesmo gráfico.</p>
        </figcaption>
        <a href="{{ $fig('01_regressao.png') }}" target="_blank" rel="noopener"><img src="{{ $fig('01_regressao.png') }}" alt="Barras com o erro médio de cada modelo e mapa de densidade comparando dias previstos e reais." loading="lazy" width="1100" height="400"></a>
    </figure>
    <figure class="cartao figura-analise">
        <figcaption>
            <h3>O que mais influencia o tempo</h3>
            <p class="como-ler"><strong>Como ler:</strong> quanto maior a barra, mais o modelo depende daquela informação. Medimos assim: embaralhamos uma informação de cada vez e vemos quanto a previsão piora ({!! $termo('importancia', 'importância por permutação') !!}). O tipo de serviço domina, e o lugar (subprefeitura) vem em seguida.</p>
        </figcaption>
        <a href="{{ $fig('02_regressao_importancia.png') }}" target="_blank" rel="noopener"><img src="{{ $fig('02_regressao_importancia.png') }}" alt="Barras com a importância de cada informação; o tipo de serviço domina, seguido da subprefeitura." loading="lazy" width="1100" height="440"></a>
    </figure>
    <p class="nota muted">Cuidado de método: só sabemos o tempo de quem já foi resolvido ({!! $termo('censura', 'censura') !!}). Para não enganar o modelo, usamos apenas chamados com pelo menos 90 dias de acompanhamento.</p>
</section>

{{-- 2. Classificação --}}
<section class="secao etapa-analise" aria-labelledby="t-classificacao">
    <h2 id="t-classificacao"><span class="passo-numero" aria-hidden="true">2</span> Classificação: este chamado vai estourar o prazo?</h2>
    <div class="em-uma-frase">
        <strong>Em uma frase:</strong> se a secretaria atender primeiro os 10% de chamados com maior nota de risco, <strong>{{ $n(100 * $clf['top10_melhor'], 0) }} em cada 100</strong> deles de fato atrasariam. Por ordem de chegada, seriam {{ $n($taxaGeral, 0) }} em 100; só olhando o tipo de serviço, {{ $n(100 * $clfBase['Precisão no top 10% de risco'], 0) }} em 100.
    </div>
    <p>{!! $termo('classificacao', 'Classificação') !!} é a técnica que responde <strong>sim ou não</strong>: "vai atrasar?". O modelo dá a cada chamado uma nota de risco de 0 a 100%.
    Aprendeu com {{ $n($clf['n_treino']) }} chamados de jan–jun e foi testado em {{ $n($clf['n_teste']) }} chamados de jul–set, dos quais {{ $n($taxaGeral, 0) }}% atrasaram.</p>
    <div class="tabela-rolagem" tabindex="0" role="region" aria-label="Comparação dos modelos de classificação">
        <table class="tabela">
            <thead><tr>
                <th scope="col">Forma de prever</th>
                <th scope="col">Acerto no top 10%<br><span class="cabecalho-ajuda">dos mais arriscados, quantos atrasaram</span></th>
                <th scope="col">Ordenação<br><span class="cabecalho-ajuda">{!! $termo('auc', 'ROC AUC') !!} · 0,5 = sorte, 1 = perfeito</span></th>
                <th scope="col">Precisão<br><span class="cabecalho-ajuda">{!! $termo('precisao', 'alarmes certos') !!}</span></th>
                <th scope="col">Recall<br><span class="cabecalho-ajuda">{!! $termo('recall', 'atrasos avisados') !!}</span></th>
                <th scope="col">F1<br><span class="cabecalho-ajuda">{!! $termo('f1', 'equilíbrio') !!}</span></th>
            </tr></thead>
            <tbody>
            @foreach($clf['modelos'] as $m)
                <tr @if($m['modelo'] === 'Gradient Boosting') class="linha-destaque" @endif>
                    <th scope="row">{{ $m['modelo'] }}@if($m['modelo'] === 'Gradient Boosting') <span class="selo-melhor">melhor</span>@endif</th>
                    <td class="mono">{{ $n(100 * $m['Precisão no top 10% de risco'], 1) }}%</td>
                    <td class="mono">{{ $n($m['ROC AUC'], 3) }}</td>
                    <td class="mono">{{ $n(100 * $m['Precisão'], 0) }}%</td>
                    <td class="mono">{{ $n(100 * $m['Recall'], 0) }}%</td>
                    <td class="mono">{{ $n($m['F1'], 2) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="como-ler"><strong>Como ler:</strong> a coluna mais útil para a gestão é a primeira. "Ordenação {{ $n($clfMelhor['ROC AUC'], 2) }}" quer dizer: pegando um chamado que atrasou e outro que não atrasou, o modelo dá nota de risco maior ao que atrasou em {{ $n(100 * $clfMelhor['ROC AUC'], 0) }}% das vezes.
    Com o limite de 50% de risco, o modelo avisa {{ $n(100 * $clfMelhor['Recall'], 0) }}% dos atrasos (recall), e {{ $n(100 * $clfMelhor['Precisão'], 0) }}% dos seus alarmes estão certos (precisão).</p>
    <p class="nota"><strong>Sendo honestos:</strong> a melhora sobre a regra de bolso é <strong>pequena</strong> ({{ $n($clfMelhor['ROC AUC'], 3) }} contra {{ $n($clfBase['ROC AUC'], 3) }}). A maior parte do risco já vem do tipo de serviço: veículo abandonado atrasa muito mais que entulho. O modelo acrescenta local, fila e calendário para <strong>ordenar melhor os chamados dentro de cada serviço</strong>.</p>
    <figure class="cartao figura-analise">
        <figcaption>
            <h3>Curva ROC e matriz de confusão</h3>
            <p class="como-ler"><strong>Como ler:</strong> à esquerda, quanto mais a curva sobe e encosta no canto superior esquerdo, melhor; a diagonal tracejada seria escolher no cara ou coroa. À direita, a {!! $termo('matriz', 'matriz de confusão') !!} conta acertos (diagonal escura) e erros: "alarme falso" é avisar um atraso que não aconteceu; "atraso não previsto" é o atraso que passou sem aviso.</p>
        </figcaption>
        <a href="{{ $fig('03_classificacao.png') }}" target="_blank" rel="noopener"><img src="{{ $fig('03_classificacao.png') }}" alt="Curvas ROC dos três modelos e matriz de confusão do Gradient Boosting no período de teste." loading="lazy" width="1100" height="420"></a>
    </figure>
</section>

{{-- 3. Clusterização --}}
<section class="secao etapa-analise" aria-labelledby="t-cluster">
    <h2 id="t-cluster"><span class="passo-numero" aria-hidden="true">3</span> Clusterização: perfis de subprefeitura e focos de buraco</h2>
    <div class="em-uma-frase">
        <strong>Em uma frase:</strong> as {{ $clu['n_subprefeituras'] }} subprefeituras se dividem em <strong>{{ $clu['k'] }} perfis</strong>. As "{{ $lentas['grupo'] }}" ({{ $lentas['membros'] }}) têm tempo típico de <strong>{{ $n($lentas['mediana (dias)'], 1) }} dias</strong> por chamado, {{ $n($razaoLentas, 1) }} vezes o das demais. Elas pedem metas e equipes próprias.
    </div>
    <p>{!! $termo('clusterizacao', 'Clusterização') !!} é a técnica que <strong>agrupa itens parecidos</strong> sem que ninguém diga antes quais são os grupos. Usamos duas:</p>
    <ul class="lista-notas">
        <li><strong>{!! $termo('kmeans', 'K-Means') !!}</strong>: agrupou as subprefeituras por desempenho (% no prazo, tempo típico, % de chamados abertos).</li>
        <li><strong>{!! $termo('dbscan', 'DBSCAN') !!}</strong>: procurou no mapa os lugares com muitos chamados de buraco concentrados num raio de 200 metros.</li>
    </ul>
    <div class="tabela-rolagem" tabindex="0" role="region" aria-label="Perfis de subprefeitura">
        <table class="tabela">
            <thead><tr><th scope="col">Perfil</th><th scope="col">Quantas</th><th scope="col">Resolvidos no prazo</th><th scope="col">Tempo típico</th><th scope="col">Ainda abertos</th><th scope="col">Quais são</th></tr></thead>
            <tbody>
            @foreach($clu['grupos'] as $g)
                <tr>
                    <th scope="row">{{ $g['grupo'] }}</th>
                    <td class="mono">{{ $g['subprefeituras'] }}</td>
                    <td class="mono">{{ $n($g['% no prazo'], 1) }}%</td>
                    <td class="mono">{{ $n($g['mediana (dias)'], 1) }} dias</td>
                    <td class="mono">{{ $n($g['% abertos'], 1) }}%</td>
                    <td class="texto-membros">{{ $g['membros'] }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="como-ler"><strong>Como ler:</strong> os nomes dos perfis vêm dos próprios resultados: "Ágeis" têm a maior taxa no prazo; "Lentas", o maior tempo típico.
    A separação entre os grupos é moderada ({!! $termo('silhueta', 'silhueta') !!} de {{ $n($clu['silhueta'], 2) }}): há subprefeituras na fronteira entre um perfil e outro.</p>
    <p class="nota">No mapa, o DBSCAN encontrou <strong>{{ $clu['dbscan']['focos'] }} focos de buraco</strong>, que reúnem {{ $n($clu['dbscan']['pct_chamados_em_focos'], 1) }}% dos chamados. São pontos onde o buraco volta a ser reclamado e candidatos a uma obra definitiva, em vez de tapa-buraco repetido.</p>
    <figure class="cartao figura-analise">
        <figcaption>
            <h3>Quantos grupos usar e como as subprefeituras se distribuem</h3>
            <p class="como-ler"><strong>Como ler:</strong> à esquerda, a pontuação de separação para cada número de grupos; escolhemos {{ $clu['k'] }} (linha tracejada), porque 2 grupos só isolavam as mais lentas. À direita, cada ponto é uma subprefeitura e a cor é o perfil; pontos próximos têm desempenho parecido. Os eixos resumem as três medidas em duas dimensões ({!! $termo('pca', 'PCA') !!}) e não têm unidade.</p>
        </figcaption>
        <a href="{{ $fig('04_clusters_subprefeituras.png') }}" target="_blank" rel="noopener"><img src="{{ $fig('04_clusters_subprefeituras.png') }}" alt="Curva de separação por número de grupos e dispersão das subprefeituras coloridas por perfil." loading="lazy" width="1100" height="430"></a>
    </figure>
    <figure class="cartao figura-analise">
        <figcaption>
            <h3>Focos de buraco na via</h3>
            <p class="como-ler"><strong>Como ler:</strong> cada ponto cinza é um local com chamado de buraco; os azuis estão dentro de um foco. Os círculos numerados marcam os 10 maiores focos. O desenho forma o contorno da cidade de São Paulo.</p>
        </figcaption>
        <a href="{{ $fig('05_focos_buraco.png') }}" target="_blank" rel="noopener"><img src="{{ $fig('05_focos_buraco.png') }}" alt="Mapa de pontos de chamados de buraco em São Paulo com os focos destacados em azul e os 10 maiores circulados." loading="lazy" width="900" height="950"></a>
    </figure>
</section>

{{-- 4. Anomalias --}}
<section class="secao etapa-analise" aria-labelledby="t-anomalias">
    <h2 id="t-anomalias"><span class="passo-numero" aria-hidden="true">4</span> Anomalias: o que foge do padrão</h2>
    <div class="em-uma-frase">
        <strong>Em uma frase:</strong> sem receber o calendário, o sistema marcou como incomuns os dias de <strong>todos os {{ $ano['feriados_no_periodo'] }} feriados</strong> do período. Isso mostra que ele aprendeu o ritmo normal da cidade e pode avisar quando algo sair dele.
    </div>
    <p>{!! $termo('anomalia', 'Detecção de anomalias') !!} procura <strong>o que é diferente do normal</strong>. Para cada dia, calculamos quantos chamados seriam esperados para aquele dia da semana e marcamos os dias muito acima ou muito abaixo disso.
    Usamos duas réguas: o {!! $termo('zscore', 'z-score robusto') !!} e o {!! $termo('isolation', 'Isolation Forest') !!}. Em {{ $ano['dias_analisados'] }} dias, {{ $ano['dias_anomalos'] }} foram marcados.</p>
    <p class="como-ler"><strong>Para que serve:</strong> os dias marcados que <em>não</em> são feriados merecem investigação: um mutirão, uma chuva forte, uma falha no sistema de atendimento.</p>
    <figure class="cartao figura-analise">
        <figcaption>
            <h3>Dias fora do padrão</h3>
            <p class="como-ler"><strong>Como ler:</strong> a linha azul é o número de chamados por dia; a tracejada cinza, o esperado. Os pontos vermelhos e amarelos são os dias marcados, com o nome do feriado quando houver. O sobe e desce semanal é normal: no fim de semana sempre há menos chamados.</p>
        </figcaption>
        <a href="{{ $fig('06_anomalias_volume.png') }}" target="_blank" rel="noopener"><img src="{{ $fig('06_anomalias_volume.png') }}" alt="Linha do volume diário de chamados com o esperado e os dias anômalos marcados, vários deles feriados." loading="lazy" width="1100" height="400"></a>
    </figure>
    <p>Também olhamos os <strong>endereços no mapa</strong>. Encontramos {{ $ano['pontos_suspeitos'] }} pontos suspeitos entre {{ $n($ano['pontos_analisados']) }}, com {{ $n($ano['chamados_em_pontos_suspeitos']) }} chamados: muitos chamados, de vários tipos, em muitos dias diferentes, no mesmo lugar exato. Isso indica que o sistema da prefeitura provavelmente usou um <strong>ponto padrão</strong> quando não conseguiu localizar o endereço. É um problema de qualidade de dados que vale corrigir na origem.</p>
    <figure class="cartao figura-analise">
        <figcaption>
            <h3>Endereços suspeitos</h3>
            <p class="como-ler"><strong>Como ler:</strong> cada ponto é um local do mapa. Quanto mais para a direita, em mais dias diferentes houve chamado ali; quanto mais para cima, mais chamados. Os vermelhos são os que fogem do padrão de um problema real.</p>
        </figcaption>
        <a href="{{ $fig('07_anomalias_coordenadas.png') }}" target="_blank" rel="noopener"><img src="{{ $fig('07_anomalias_coordenadas.png') }}" alt="Dispersão de locais por número de dias e de chamados, com os suspeitos destacados em vermelho." loading="lazy" width="1100" height="520"></a>
    </figure>
</section>

<section class="secao etapa-analise" aria-labelledby="t-valor">
    <h2 id="t-valor">O que isso muda no Cidade Alfa 156+</h2>
    <ol class="passos conclusoes">
        <li><div><strong>Prazo mais realista para o cidadão</strong><p class="muted">Em vez de um prazo fixo por serviço, uma estimativa que considera o local.</p></div></li>
        <li><div><strong>Fila por risco</strong><p class="muted">A secretaria atende primeiro o que tem mais chance de atrasar.</p></div></li>
        <li><div><strong>Gestão por perfil</strong><p class="muted">Metas e reforço de equipe por perfil de subprefeitura; obras definitivas nos focos.</p></div></li>
        <li><div><strong>Vigilância automática</strong><p class="muted">Aviso de dias atípicos e lista de endereços mal localizados.</p></div></li>
    </ol>
    <p class="nota muted">Limitações: a base não tem horário de abertura nem número de protocolo, e os prazos são os do catálogo fictício da Cidade Alfa. Fonte: dados abertos do SP156 (CC0). Ferramenta: scikit-learn {{ $ml['sklearn'] }}.</p>
</section>

{{-- Glossário --}}
<section class="secao" aria-labelledby="glossario">
    <h2 id="glossario">Glossário: os termos em linguagem simples</h2>
    <p class="muted">Clique em qualquer termo sublinhado na página para chegar aqui.</p>
    @php
        $glossario = [
            'Conceitos gerais' => [
                ['baseline', 'Baseline (regra de bolso)', 'A forma mais simples de prever, sem modelo, como "buraco costuma levar 7 dias". Serve de régua: um modelo só vale a pena se for melhor que ela.'],
                ['vazamento', 'Vazamento de dados', 'Usar no treino uma informação que só existe depois do fato, como saber o fim do filme antes de assistir. O resultado parece ótimo no teste e falha na vida real. Evitamos usando só o que se sabe na abertura do chamado.'],
                ['validacao-temporal', 'Validação temporal', 'Treinar com o passado e testar com um período posterior, que o modelo nunca viu. É como o modelo seria usado de verdade.'],
                ['censura', 'Censura', 'Quando ainda não sabemos o desfecho de parte dos casos. Aqui, os chamados ainda abertos: não sabemos quanto tempo vão levar, e costumam ser os mais demorados.'],
                ['log', 'Escala logarítmica', 'Um eixo em que 1, 10 e 100 ficam igualmente espaçados. Permite ver juntos chamados de 1 dia e de 200 dias.'],
                ['mediana', 'Mediana (valor típico)', 'O valor do meio: metade dos casos fica abaixo e metade acima. Diferente da média, não é puxada por poucos casos extremos.'],
            ],
            'Regressão (prever um número)' => [
                ['regressao', 'Regressão', 'Técnica que prevê um número, como dias até a resolução.'],
                ['linear', 'Regressão Linear', 'Modelo clássico que soma o peso de cada fator ("serviço X acrescenta tantos dias"). Simples de explicar, mas não capta combinações.'],
                ['boosting', 'Gradient Boosting', 'Muitas pequenas árvores de decisão ("se for buraco e na zona oeste, então...") em sequência, cada uma corrigindo os erros da anterior. Costuma ser o mais preciso com dados em tabela.'],
                ['mae', 'MAE (erro absoluto médio)', 'Em média, quantos dias a previsão erra, para mais ou para menos. Menor é melhor.'],
                ['r2', 'R² (coeficiente de determinação)', 'Quanto das diferenças entre chamados o modelo consegue explicar: 0 = nada, 1 = tudo. Calculado em escala logarítmica, para que erros em chamados curtos e longos pesem de forma parecida.'],
                ['importancia', 'Importância por permutação', 'Embaralhamos uma informação de cada vez e vemos quanto o modelo piora. Quanto mais piora, mais ele dependia daquela informação.'],
            ],
            'Classificação (sim ou não)' => [
                ['classificacao', 'Classificação', 'Técnica que prevê uma categoria, como "vai atrasar" ou "não vai atrasar", com uma nota de probabilidade.'],
                ['auc', 'ROC AUC (capacidade de ordenar)', 'Se pegarmos um chamado que atrasou e um que não atrasou, é a chance de o modelo dar nota de risco maior ao que atrasou. 0,5 = cara ou coroa; 1 = acerta sempre.'],
                ['precisao', 'Precisão (alarmes certos)', 'Dos chamados que o modelo avisou que iam atrasar, quantos realmente atrasaram.'],
                ['recall', 'Recall (atrasos avisados)', 'Dos chamados que realmente atrasaram, quantos o modelo avisou antes.'],
                ['f1', 'F1', 'Uma nota que equilibra precisão e recall. Vai de 0 a 1.'],
                ['matriz', 'Matriz de confusão', 'Tabela 2×2 que conta acertos e erros: atrasos previstos, alarmes falsos, atrasos não previstos e chamados no prazo corretamente identificados.'],
            ],
            'Clusterização (agrupar parecidos)' => [
                ['clusterizacao', 'Clusterização', 'Técnica que agrupa itens parecidos sem que ninguém diga antes quais são os grupos.'],
                ['kmeans', 'K-Means', 'Divide os itens em K grupos, juntando cada um ao "centro" mais próximo. Nós escolhemos o K.'],
                ['silhueta', 'Silhueta', 'Nota de 0 a 1 de quão bem separados estão os grupos: perto de 1 = bem separados; perto de 0 = se misturam.'],
                ['pca', 'PCA', 'Forma de resumir várias medidas em duas, para desenhar num gráfico. Pontos próximos são parecidos; os eixos não têm unidade.'],
                ['dbscan', 'DBSCAN', 'Encontra concentrações de pontos no mapa (aqui, muitos chamados num raio de 200 m) e deixa de fora os pontos isolados. Não precisa saber quantos focos existem.'],
            ],
            'Anomalias (o que foge do padrão)' => [
                ['anomalia', 'Anomalia', 'Um caso muito diferente do comportamento normal: um dia com volume estranho, um endereço improvável.'],
                ['zscore', 'Z-score robusto', 'Mede quantas "distâncias normais" um dia está longe do esperado. Acima de 3,5 é muito incomum.'],
                ['isolation', 'Isolation Forest', 'Algoritmo que separa os casos fazendo cortes aleatórios. Os diferentes ficam isolados com poucos cortes; por isso são marcados como anomalias.'],
            ],
        ];
    @endphp
    <div class="glossario">
        @foreach($glossario as $grupo => $itens)
            <div class="cartao">
                <h3>{{ $grupo }}</h3>
                <dl>
                    @foreach($itens as [$id, $nome, $def])
                        <div id="g-{{ $id }}" class="glossario-item"><dt>{{ $nome }}</dt><dd>{{ $def }}</dd></div>
                    @endforeach
                </dl>
            </div>
        @endforeach
    </div>
</section>
@endsection
