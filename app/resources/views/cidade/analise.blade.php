@extends('cidade.layout')
@section('titulo', 'Análise de dados')
@php
    $n = fn ($v, $d = 0) => number_format((float) $v, $d, ',', '.');
    $pct = fn ($parte, $todo) => $todo ? $n(100 * $parte / $todo, 1).'%' : '—';
    $q = $eda['qualidade'];
    $sla = $eda['sla_geral'];
    $fig = fn ($nome) => asset('fase4/figuras/'.$nome);
    $colab = 'https://colab.research.google.com/github/'.$notebook;
    $github = 'https://github.com/'.$notebook;
@endphp
@section('conteudo')
<section class="hero">
    <p class="rotulo">Fase 4 · Exploração e preparação de dados</p>
    <h1>Como analisamos {{ $n($eda['linhas_brutas'] / 1e6, 1) }} milhão de chamados</h1>
    <p class="texto-grande">O passo a passo da preparação dos dados reais do SP156 para medir se os chamados são resolvidos dentro do prazo: da planilha bruta aos indicadores.</p>
    <div class="botoes">
        <a class="botao botao-primario" href="{{ $colab }}" target="_blank" rel="noopener">@include('cidade.icone', ['nome' => 'codigo', 'tamanho' => 20]) Abrir o notebook no Google Colab</a>
        <a class="botao botao-secundario" href="{{ $github }}" target="_blank" rel="noopener">Ver no GitHub</a>
        <a class="botao botao-secundario" href="{{ asset('fase4/eda_sp156.ipynb') }}" download>Baixar .ipynb</a>
    </div>
</section>

{{-- Camadas da arquitetura da Fase 3 --}}
<ol class="camadas" aria-label="Camadas de dados">
    <li class="camada">
        <span class="camada-nome">Raw</span>
        <strong class="mono">{{ $n($eda['linhas_brutas']) }}</strong>
        <span class="muted">chamados em {{ $eda['arquivos'] }} planilhas ({{ $eda['periodo'] }})</span>
    </li>
    <li class="camada">
        <span class="camada-nome">Prata</span>
        <strong class="mono">{{ $n($eda['linhas_brutas']) }}</strong>
        <span class="muted">limpos: nenhum apagado, problemas marcados</span>
    </li>
    <li class="camada">
        <span class="camada-nome">Ouro</span>
        <strong class="mono">{{ $n($eda['linhas_base_sla']) }}</strong>
        <span class="muted">chamados dos 6 serviços com atributos de prazo</span>
    </li>
</ol>

{{-- 1. Coletar --}}
<section class="secao etapa-analise" aria-labelledby="passo-1">
    <h2 id="passo-1"><span class="passo-numero" aria-hidden="true">1</span> Coletar</h2>
    <p>Três planilhas trimestrais dos <a href="https://dados.prefeitura.sp.gov.br/dataset/dados-do-sp156" rel="noopener">dados abertos do SP156</a>
    (Prefeitura de São Paulo, licença CC0). Usamos o formato XLSX: as datas vêm tipadas, enquanto o CSV do portal usa mês/dia/ano.
    A Cidade Alfa é fictícia e usa São Paulo como referência real.</p>
    <ul class="resumo" aria-label="Status na base">
        @foreach(['finalizada' => ['Finalizados', 'resumo-leaf'], 'aberta' => ['Abertos', 'resumo-sky'], 'em_andamento' => ['Em andamento', 'resumo-berry'], 'cancelada' => ['Cancelados', 'resumo-neutro']] as $chave => [$rotulo, $classe])
            <li class="resumo-item {{ $classe }}"><span class="resumo-numero mono">{{ $n($eda['status'][$chave] ?? 0) }}</span><span>{{ $rotulo }}</span></li>
        @endforeach
    </ul>
</section>

{{-- 2. Diagnosticar --}}
<section class="secao etapa-analise" aria-labelledby="passo-2">
    <h2 id="passo-2"><span class="passo-numero" aria-hidden="true">2</span> Diagnosticar a qualidade</h2>
    <p>Antes de mexer em qualquer dado, medimos os problemas.</p>
    <ul class="kpis">
        <li class="cartao kpi">
            <span class="kpi-rotulo">Sem coordenadas</span>
            <span class="kpi-numero mono">{{ $pct($q['sem_coordenadas'], $eda['linhas_brutas']) }}</span>
            <span class="muted">Serviços sem local físico, como cadastro de animais e ouvidoria. Ficam fora da análise espacial.</span>
        </li>
        <li class="cartao kpi">
            <span class="kpi-rotulo">Linhas idênticas</span>
            <span class="kpi-numero mono">{{ $pct($q['duplicatas_exatas_todas_colunas'], $eda['linhas_brutas']) }}</span>
            <span class="muted">Não dá para deduplicar: a base aberta não traz protocolo, e pedidos diferentes sem endereço ficam iguais.</span>
        </li>
        <li class="cartao kpi">
            <span class="kpi-rotulo">Finalizados sem data</span>
            <span class="kpi-numero mono">{{ $n($q['finalizada_sem_data_finalizacao']) }}</span>
            <span class="muted">Ficam fora do cálculo de tempo de atendimento.</span>
        </li>
    </ul>
    <p class="nota">Também encontramos nomes de subprefeitura sem acento e <strong>dois nomes trocados no meio do ano</strong>
    (Perus → Perus-Anhanguera; Casa Verde-Cachoeirinha → Casa Verde-Limão-Cachoeirinha).
    Nenhum chamado tinha abertura vazia ou finalização antes da abertura.</p>
</section>

{{-- 3. Higienizar --}}
<section class="secao etapa-analise" aria-labelledby="passo-3">
    <h2 id="passo-3"><span class="passo-numero" aria-hidden="true">3</span> Higienizar</h2>
    <p>Princípio: <strong>nenhum chamado é apagado sem motivo</strong>. Cada problema vira uma marcação, e cada métrica decide o que deixar de fora.</p>
    <div class="tabela-rolagem" tabindex="0" role="region" aria-labelledby="passo-3">
        <table class="tabela">
            <thead><tr><th scope="col">Problema</th><th scope="col">Chamados</th><th scope="col">Decisão</th></tr></thead>
            <tbody>
            @foreach($eda['decisoes_limpeza'] as $d)
                <tr><th scope="row">{{ $d['etapa'] }}</th><td class="mono">{{ $n($d['linhas_afetadas']) }}</td><td>{{ $d['decisao'] }}</td></tr>
            @endforeach
                <tr><th scope="row">Tempo de atendimento muito acima do normal (regra de Tukey)</th><td class="mono">{{ $n($eda['outliers_tempo']) }}</td><td>mantido com marcação; médias usam tempo limitado ao percentil 99</td></tr>
                <tr><th scope="row">Aberto há mais de 100 dias na data de extração</th><td class="mono">{{ $n($sla['backlog_mais_100_dias']) }}</td><td>mantido com marcação; analisado à parte</td></tr>
            </tbody>
        </table>
    </div>
    <p class="nota muted">Os tempos longos são reais: descartá-los esconderia justamente o problema que o projeto quer tornar visível.</p>
</section>

{{-- 4. Criar atributos --}}
<section class="secao etapa-analise" aria-labelledby="passo-4">
    <h2 id="passo-4"><span class="passo-numero" aria-hidden="true">4</span> Criar atributos</h2>
    <p>Para cada chamado dos 6 serviços do app, calculamos o prazo e a situação em relação a ele.</p>
    <dl class="atributos">
        <div class="cartao"><dt class="mono">estouro_sla_dias</dt><dd>Data de conclusão (ou de extração, se aberto) menos a data-limite do prazo. Positivo = dias de atraso.</dd></div>
        <div class="cartao"><dt class="mono">flag_cumpriu_sla</dt><dd>1 se resolvido no prazo, 0 se atrasou, vazio se ainda está aberto dentro do prazo.</dd></div>
        <div class="cartao"><dt class="mono">faixa_atendimento</dt><dd>Atendido em 24h, no prazo, atraso leve (até o dobro do prazo) ou atraso crítico.</dd></div>
        <div class="cartao"><dt class="mono">score_prioridade_comunidade</dt><dd>Urgência do serviço × (1 + log₂(1 + apoios)). Como a base não tem apoios, usamos as reclamações repetidas do mesmo problema ({{ $n($eda['com_repeticao_pct'], 1) }}% dos chamados).</dd></div>
    </dl>
</section>

{{-- 5. Explorar --}}
<section class="secao etapa-analise" aria-labelledby="passo-5">
    <h2 id="passo-5"><span class="passo-numero" aria-hidden="true">5</span> Explorar</h2>
    <ul class="kpis">
        <li class="cartao kpi">
            <span class="kpi-rotulo">Resolvidos no prazo</span>
            <span class="kpi-numero mono">{{ $n($sla['pct_no_prazo'], 1) }}<small>%</small></span>
            <span class="muted">de {{ $n($sla['finalizados']) }} chamados finalizados nos 6 serviços</span>
        </li>
        <li class="cartao kpi">
            <span class="kpi-rotulo">Tempo típico</span>
            <span class="kpi-numero mono">{{ $n($sla['mediana_dias']) }}<small> dias</small></span>
            <span class="muted">mediana dos chamados resolvidos</span>
        </li>
        <li class="cartao kpi">
            <span class="kpi-rotulo">Abertos e atrasados</span>
            <span class="kpi-numero mono">{{ $n($sla['abertos_atrasados_na_corte']) }}</span>
            <span class="muted">de {{ $n($sla['abertos_na_corte']) }} abertos na data de extração</span>
        </li>
    </ul>

    <h3 class="subtitulo-analise">Tempo e prazo por serviço</h3>
    <div class="tabela-rolagem" tabindex="0" role="region" aria-label="Tempo de atendimento por serviço">
        <table class="tabela">
            <thead><tr><th scope="col">Serviço</th><th scope="col">Finalizados</th><th scope="col">Mediana</th><th scope="col">Média</th><th scope="col">Desvio</th><th scope="col">Prazo</th><th scope="col">No prazo</th><th scope="col">Abertos</th></tr></thead>
            <tbody>
            @foreach($eda['tempo_por_servico'] as $s)
                <tr>
                    <th scope="row">{{ $s['servico'] }}</th>
                    <td class="mono">{{ $n($s['finalizados']) }}</td>
                    <td class="mono">{{ $n($s['mediana']) }} d</td>
                    <td class="mono">{{ $n($s['media'], 1) }} d</td>
                    <td class="mono">{{ $n($s['desvio_padrao'], 1) }}</td>
                    <td class="mono">{{ $s['sla_dias'] }} d</td>
                    <td>@include('cidade._medidor', ['pct' => (int) round($s['pct_no_prazo'])])</td>
                    <td class="mono">{{ $n($s['pct_abertos'], 1) }}%</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>

    @foreach([
        ['01_tempo_atendimento_por_servico.png', 'Distribuição do tempo de atendimento',
         'A média fica bem acima da mediana em todos os serviços: poucos chamados muito demorados puxam a média. Por isso os painéis usam mediana e percentis, não média simples.',
         'Diagrama de caixa do tempo de atendimento por serviço em escala logarítmica, com o prazo marcado. Entulho e bueiro têm mediana de 1 dia; veículo abandonado, 36 dias.'],
        ['02_faixas_de_atendimento.png', 'Faixas de atendimento',
         'Entulho e bueiro resolvem mais da metade em até 24 horas. Em buraco na via e em guias e sarjetas, cerca de um terço termina em atraso crítico.',
         'Barras empilhadas com a proporção de chamados atendidos em 24h, no prazo, com atraso leve e com atraso crítico, por serviço.'],
        ['03_volume_por_prefeitura.png', 'Onde estão os chamados',
         'A Sé concentra o maior volume ('.$n(array_values($eda['top_prefeituras'])[0]).' chamados), seguida de '.implode(' e ', array_slice(array_keys($eda['top_prefeituras']), 1, 2)).'.',
         'Barras com as 15 subprefeituras com mais chamados.'],
        ['04_prazo_por_prefeitura.png', 'Prazo cumprido por subprefeitura',
         'Do melhor ('.$eda['prazo_prefeitura']['melhor'].', '.$n($eda['prazo_prefeitura']['melhor_pct'], 1).'%) ao pior ('.$eda['prazo_prefeitura']['pior'].', '.$n($eda['prazo_prefeitura']['pior_pct'], 1).'%) são mais de 25 pontos de diferença: um sinal de onde a operação trava.',
         'Barras com o percentual de chamados resolvidos no prazo nas 8 piores e 8 melhores subprefeituras.'],
        ['05_volume_por_dia_da_semana.png', 'Dias da semana',
         'O fim de semana tem só '.$n($eda['fim_de_semana_pct'], 1).'% dos chamados: o volume cai pela metade no sábado e no domingo.',
         'Barras com a média de chamados por dia da semana.'],
        ['06_tendencia_semanal.png', 'Tendência ao longo do ano',
         'Pico na segunda semana de janeiro, vale no inverno (junho e julho) e retomada em agosto.',
         'Linha com o total de chamados por semana, de janeiro a setembro de 2026.'],
        ['08_canais.png', 'Canais de abertura',
         'Na base toda o Portal lidera, mas nos serviços com local o celular é o canal principal (mais da metade dos buracos). Isso reforça um app com mapa e geolocalização.',
         'Barras com o percentual de chamados por canal: Portal, CRM, Mobile, Integração e Chatbot.'],
        ['09_duplicatas_por_servico.png', 'O mesmo problema registrado várias vezes',
         'Até 14% dos chamados de veículo abandonado repetem outro já aberto no mesmo dia e ponto. É a evidência para o apoio comunitário: em vez de abrir outro chamado, o cidadão apoia o existente.',
         'Barras com o percentual de chamados que repetem outro já aberto, por serviço.'],
    ] as [$arquivo, $titulo, $leitura, $alt])
        <figure class="cartao figura-analise">
            <figcaption>
                <h3>{{ $titulo }}</h3>
                <p>{{ $leitura }}</p>
            </figcaption>
            <a href="{{ $fig($arquivo) }}" target="_blank" rel="noopener" title="Abrir em tamanho original">
                <img src="{{ $fig($arquivo) }}" alt="{{ $alt }}" loading="lazy" width="1100" height="560">
            </a>
        </figure>
    @endforeach
</section>

{{-- 6. Conclusões --}}
<section class="secao etapa-analise" aria-labelledby="passo-6">
    <h2 id="passo-6"><span class="passo-numero" aria-hidden="true">6</span> O que levamos para a Fase 5</h2>
    <ol class="passos conclusoes">
        <li><div><strong>Qualidade tratada e documentada</strong><p class="muted">Sem protocolo, 37% sem local e nomes inconsistentes: tudo marcado, nada apagado às cegas.</p></div></li>
        <li><div><strong>Prazo varia muito</strong><p class="muted">De 95% no prazo (entulho) a cerca de 40% (veículo abandonado), e mais de 25 pontos entre subprefeituras.</p></div></li>
        <li><div><strong>Mediana, não média</strong><p class="muted">Tempos com cauda longa pedem painéis com mediana e percentis.</p></div></li>
        <li><div><strong>Celular e comunidade</strong><p class="muted">O cidadão já usa o celular para problemas com local, e as reclamações repetidas mostram espaço para o apoio comunitário.</p></div></li>
    </ol>
    <p class="nota">A base final já traz os campos dos painéis da Fase 5: <span class="mono">flag_cumpriu_sla</span>, <span class="mono">estouro_sla_dias</span>,
    <span class="mono">faixa_atendimento</span>, <span class="mono">score_prioridade_comunidade</span>, calendário e geografia.
    Veja também os <a href="{{ route('indicadores') }}">indicadores de prazo</a> do app.</p>
</section>
@endsection
