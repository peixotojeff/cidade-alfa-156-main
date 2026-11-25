@extends('cidade.layout')
@section('titulo', 'Início')
@section('conteudo')
<section class="hero">
    <p class="rotulo">Serviços municipais · Demonstração</p>
    <h1>Viu um problema na cidade? Conte para a gente.</h1>
    <p class="texto-grande">Registre em poucos minutos, marque o local no mapa e acompanhe cada etapa pelo número de protocolo.</p>
</section>

<section class="secao" aria-labelledby="o-que-fazer">
    <h2 id="o-que-fazer" class="visualmente-oculto">O que você quer fazer?</h2>
    <div class="caminhos">
        <a class="caminho caminho-primario" href="{{ route('novo') }}">
            <span class="caminho-icone">@include('cidade.icone', ['nome' => 'pin', 'tamanho' => 32])</span>
            <span class="caminho-texto"><strong>Abrir um chamado</strong><span>Informe o problema e onde ele está.</span></span>
            @include('cidade.icone', ['nome' => 'seta', 'classe' => 'caminho-seta'])
        </a>
        <a class="caminho" href="{{ route('consulta') }}">
            <span class="caminho-icone">@include('cidade.icone', ['nome' => 'lupa', 'tamanho' => 32])</span>
            <span class="caminho-texto"><strong>Acompanhar um chamado</strong><span>Use o protocolo que você recebeu.</span></span>
            @include('cidade.icone', ['nome' => 'seta', 'classe' => 'caminho-seta'])
        </a>
        <a class="caminho" href="{{ route('indicadores') }}">
            <span class="caminho-icone">@include('cidade.icone', ['nome' => 'grafico', 'tamanho' => 32])</span>
            <span class="caminho-texto"><strong>Ver os prazos</strong><span>Quanto a prefeitura resolve dentro do prazo.</span></span>
            @include('cidade.icone', ['nome' => 'seta', 'classe' => 'caminho-seta'])
        </a>
    </div>
</section>

<section class="secao" aria-labelledby="servicos">
    <h2 id="servicos">Serviços disponíveis</h2>
    <p class="muted">Cada serviço tem um prazo previsto de atendimento, contado em dias corridos.</p>
    <ul class="grade-servicos">
        @foreach($servicos as $codigo => $item)
            <li class="cartao servico">
                <span class="servico-icone">@include('cidade.icone', ['nome' => $item['icone'], 'tamanho' => 32])</span>
                <h3>{{ $item['nome'] }}</h3>
                <p class="muted">{{ $item['exemplo'] }}</p>
                <p class="servico-meta">
                    <span class="prazo">@include('cidade.icone', ['nome' => 'relogio', 'tamanho' => 20]) {{ $item['dias'] }} dias</span>
                    <span class="muted">{{ $item['secretaria'] }}</span>
                </p>
            </li>
        @endforeach
    </ul>
</section>

<section class="secao" aria-labelledby="como-funciona">
    <h2 id="como-funciona">Como funciona</h2>
    <ol class="passos">
        <li><span class="passo-numero" aria-hidden="true">1</span><div><strong>Conte o que aconteceu</strong><p class="muted">Escolha o serviço e descreva o problema.</p></div></li>
        <li><span class="passo-numero" aria-hidden="true">2</span><div><strong>Marque o local</strong><p class="muted">Toque no mapa, use sua localização ou digite as coordenadas.</p></div></li>
        <li><span class="passo-numero" aria-hidden="true">3</span><div><strong>Acompanhe pelo protocolo</strong><p class="muted">Veja quando a secretaria recebe, atende e resolve.</p></div></li>
    </ol>
</section>
@endsection
