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

@if($demanda->ehExemplo())
    <div class="alerta alerta-info" role="note">
        @include('cidade.icone', ['nome' => 'lampada'])
        <div>
            <p><strong>Chamado de exemplo.</strong> Mostra o fluxo completo que o Cidade Alfa 156+ propõe: cada etapa com data e hora, aviso ao cidadão a cada mudança, o prazo à vista do início ao fim e a avaliação do atendimento no final.</p>
            <p class="muted">Dados fictícios. Este chamado não entra nos indicadores.</p>
        </div>
    </div>
@endif

<div class="cabecalho-pagina">
    <p class="rotulo">{{ $demanda->ehExemplo() ? 'Exemplo · chamado resolvido' : ($demanda->veioDoSp156() ? 'Chamado do SP156 · dados abertos' : 'Seu chamado') }}</p>
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
                            @if($evento->notificacao_simulada)
                                <span class="aviso-enviado">@include('cidade.icone', ['nome' => 'sino', 'tamanho' => 20]) Aviso ao cidadão (simulado)</span>
                            @endif
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
            <div><dt>Prazo previsto</dt><dd><span class="prazo">@include('cidade.icone', ['nome' => 'relogio', 'tamanho' => 20]) {{ $demanda->prazo->format('d/m/Y') }}</span> <span class="muted">({{ $servico['dias'] }} dias)</span>
                @if(($folga = $demanda->folgaDoPrazo()) !== null)
                    <span class="folga {{ $folga >= 0 ? 'folga-ok' : 'folga-atraso' }}">
                        @if($folga > 0) Resolvido {{ $folga }} {{ $folga === 1 ? 'dia' : 'dias' }} antes do prazo
                        @elseif($folga === 0) Resolvido no último dia do prazo
                        @else Resolvido {{ -$folga }} {{ $folga === -1 ? 'dia' : 'dias' }} depois do prazo @endif
                    </span>
                @endif
            </dd></div>
            <div><dt>Responsável</dt><dd>Secretaria de {{ $servico['secretaria'] }}</dd></div>
            @if($endereco = $demanda->endereco())
                <div><dt>Endereço</dt><dd>{{ $endereco }}</dd></div>
            @endif
            <div><dt>Coordenadas</dt><dd class="mono">{{ $demanda->latitude }}, {{ $demanda->longitude }}</dd></div>
            <div><dt>Descrição</dt><dd class="texto-normal">{{ $demanda->descricao }}</dd></div>
        </dl>
        @if($demanda->veioDoSp156())
            <p class="selo-fonte">@include('cidade.icone', ['nome' => 'grafico', 'tamanho' => 20]) <span>Chamado real dos <a href="https://dados.prefeitura.sp.gov.br/dataset/dados-do-sp156" rel="noopener">dados abertos do SP156</a>, sem dados pessoais. Mostra a situação na data da extração ({{ \App\Models\Demanda::corteSp156($demanda->created_at)?->format('d/m/Y') }}); é somente leitura e não recebe avaliação.</span></p>
        @endif
    </section>
</div>

@if($demanda->avaliacao || $demanda->podeSerAvaliada())
    <section id="avaliacao" class="cartao avaliacao" aria-labelledby="avaliacao-titulo">
        @if($demanda->avaliacao)
            @php $a = \App\Models\Demanda::AVALIACOES[$demanda->avaliacao]; @endphp
            <h2 id="avaliacao-titulo">Avaliação do atendimento</h2>
            @if(session('avaliado'))
                <p class="alerta alerta-sucesso" role="status">@include('cidade.icone', ['nome' => 'check']) <span>Obrigado! Sua avaliação ajuda a secretaria a melhorar o atendimento.</span></p>
            @endif
            <p class="avaliacao-feita">
                <span class="pilula pilula-{{ $a['tom'] }} pilula-grande">@include('cidade.icone', ['nome' => $a['icone']]) {{ $a['rotulo'] }}</span>
                @if($demanda->avaliada_em)<span class="muted">avaliado em <span class="mono">{{ $demanda->avaliada_em->format('d/m/Y') }}</span></span>@endif
            </p>
        @else
            <h2 id="avaliacao-titulo">Como foi o atendimento?</h2>
            <p class="muted">Seu chamado foi resolvido. Conte como foi: leva um toque e ajuda a medir a qualidade do serviço.</p>
            @error('avaliacao')<p class="erro" role="alert">@include('cidade.icone', ['nome' => 'alerta', 'tamanho' => 20]) {{ $message }}</p>@enderror
            <form method="post" action="{{ route('avaliar', $demanda->protocolo) }}" class="opcoes-avaliacao">
                @csrf
                @foreach(\App\Models\Demanda::AVALIACOES as $chave => $a)
                    <button type="submit" name="avaliacao" value="{{ $chave }}" class="botao-avaliacao botao-avaliacao-{{ $a['tom'] }}">
                        @include('cidade.icone', ['nome' => $a['icone'], 'tamanho' => 32]) {{ $a['rotulo'] }}
                    </button>
                @endforeach
            </form>
        @endif
    </section>
@endif

<p class="nota"><a href="{{ route('novo') }}">Abrir outro chamado</a> · <a href="{{ route('consulta') }}">Consultar outro protocolo</a> · <a href="{{ route('indicadores') }}">Ver indicadores de prazo</a></p>
@endsection
