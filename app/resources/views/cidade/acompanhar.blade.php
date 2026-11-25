@extends('cidade.layout')
@section('titulo', 'Seu chamado')
@php
    $etapas = \App\Models\Demanda::STATUS;
    $chaves = array_keys($etapas);
    $atual = array_search($demanda->status, $chaves, true);
    $visual = \App\Models\Demanda::VISUAL[$demanda->status];
    $quando = $historico->keyBy('status');
    $percentual = (int) round(($atual + 1) / count($chaves) * 100);
    $formatar = fn ($momento) => ($m = \Illuminate\Support\Carbon::parse($momento))->format($m->format('H:i:s') === '00:00:00' ? 'd/m/Y' : 'd/m/Y H:i');
@endphp
@section('conteudo')
@if(session('novo'))
    <div class="alerta alerta-sucesso" role="status">
        @include('cidade.icone', ['nome' => 'check'])
        <div><p><strong>Recebemos seu chamado.</strong></p><p>Guarde o protocolo <span class="mono">{{ $demanda->protocolo }}</span> para voltar a esta página.</p></div>
    </div>
@endif

<div class="cabecalho-pagina">
    <p class="rotulo">Seu chamado</p>
    <h1>{{ $servico['nome'] }}</h1>
    <p class="etiquetas">
        <span class="pilula pilula-{{ $visual['tom'] }}">@include('cidade.icone', ['nome' => $visual['icone'], 'tamanho' => 20]) {{ $etapas[$demanda->status] }}</span>
        @include('cidade._prazo', ['demanda' => $demanda])
    </p>
    <p class="texto-grande">{{ $visual['resumo'] }}</p>
</div>

<div class="colunas">
    <section class="cartao" aria-labelledby="andamento">
        <h2 id="andamento">Andamento</h2>
        <div class="progresso" role="progressbar" aria-label="Andamento do chamado" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $percentual }}" aria-valuetext="Etapa {{ $atual + 1 }} de {{ count($chaves) }}: {{ $etapas[$demanda->status] }}">
            <span style="--valor: {{ $percentual }}%"></span>
        </div>
        <ol class="linha-tempo">
            @foreach($chaves as $i => $chave)
                @php $estado = ($i < $atual || ($i === $atual && $demanda->status === 'resolvida')) ? 'feito' : ($i === $atual ? 'atual' : 'pendente'); @endphp
                <li class="etapa-{{ $estado }}" @if($i === $atual) aria-current="step" @endif>
                    <span class="marcador">@include('cidade.icone', ['nome' => $estado === 'feito' ? 'check' : \App\Models\Demanda::VISUAL[$chave]['icone'], 'tamanho' => 20])</span>
                    <div>
                        <strong>{{ $etapas[$chave] }}</strong>
                        @if($evento = $quando->get($chave))
                            <span class="muted mono">{{ $formatar($evento->ocorrido_em) }}</span>
                        @elseif($estado === 'pendente')
                            <span class="muted">Ainda não aconteceu</span>
                        @else
                            <span class="muted">Data não informada</span>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    <section class="cartao" aria-labelledby="detalhes">
        <h2 id="detalhes">Detalhes</h2>
        <dl class="dados">
            <div><dt>Protocolo</dt><dd class="mono">{{ $demanda->protocolo }}</dd></div>
            <div><dt>Prazo previsto</dt><dd><span class="prazo">@include('cidade.icone', ['nome' => 'relogio', 'tamanho' => 20]) {{ $demanda->prazo->format('d/m/Y') }}</span> <span class="muted">({{ $servico['dias'] }} dias)</span></dd></div>
            <div><dt>Responsável</dt><dd>Secretaria de {{ $servico['secretaria'] }}</dd></div>
            @if($endereco = $demanda->endereco())
                <div><dt>Endereço</dt><dd>{{ $endereco }}</dd></div>
            @endif
            <div><dt>Coordenadas</dt><dd class="mono">{{ $demanda->latitude }}, {{ $demanda->longitude }}</dd></div>
            <div><dt>Descrição</dt><dd class="texto-normal">{{ $demanda->descricao }}</dd></div>
        </dl>
        @if($demanda->veioDoSp156())
            <p class="selo-fonte">@include('cidade.icone', ['nome' => 'grafico', 'tamanho' => 20]) <span>Chamado real dos <a href="https://dados.prefeitura.sp.gov.br/dataset/dados-do-sp156" rel="noopener">dados abertos do SP156</a>, sem dados pessoais.</span></p>
        @endif
    </section>
</div>

<p class="nota"><a href="{{ route('novo') }}">Abrir outro chamado</a> · <a href="{{ route('consulta') }}">Consultar outro protocolo</a> · <a href="{{ route('indicadores') }}">Ver indicadores de prazo</a></p>
@endsection
