@extends('cidade.layout')
@section('titulo', 'Secretaria')
@php
    $g = $indicadores['geral'];
    $situacoes = ['abertos' => 'Abertos', 'atrasados' => 'Atrasados', 'resolvidos' => 'Resolvidos', 'todos' => 'Todos'];
    $link = fn (array $mudar) => route('secretaria', array_filter(array_merge($filtros, $mudar), fn ($v) => $v !== null && $v !== ''));
@endphp
@section('conteudo')
<div class="cabecalho-pagina">
    <p class="rotulo">Painel da secretaria · Demonstração</p>
    <h1>Fila de chamados</h1>
    <p>{{ $simuladas }} {{ $simuladas === 1 ? 'notificação simulada' : 'notificações simuladas' }}. Nenhuma mensagem real é enviada.</p>
</div>

@if(session('aviso'))
    <div class="alerta alerta-sucesso" role="status">@include('cidade.icone', ['nome' => 'check'])<p>{{ session('aviso') }}</p></div>
@endif

<ul class="resumo" aria-label="Resumo">
    @foreach(\App\Models\Demanda::STATUS as $chave => $rotulo)
        @php $v = \App\Models\Demanda::VISUAL[$chave]; @endphp
        <li class="resumo-item resumo-{{ $v['tom'] }}">
            @include('cidade.icone', ['nome' => $v['icone']])
            <span class="resumo-numero mono">{{ $contagem[$chave] ?? 0 }}</span>
            <span>{{ $rotulo }}</span>
        </li>
    @endforeach
    <li class="resumo-item resumo-coral">
        @include('cidade.icone', ['nome' => 'alerta'])
        <span class="resumo-numero mono">{{ $g['atrasadas'] }}</span>
        <span>Abertos atrasados</span>
    </li>
    <li class="resumo-item resumo-neutro">
        @include('cidade.icone', ['nome' => 'grafico'])
        <span class="resumo-numero mono">{{ $g['pct_no_prazo'] ?? '—' }}{{ $g['pct_no_prazo'] !== null ? '%' : '' }}</span>
        <span>Resolvidos no prazo · <a href="{{ route('indicadores') }}">detalhes</a></span>
    </li>
</ul>

<form class="filtros" method="get" action="{{ route('secretaria') }}" aria-label="Filtrar a fila">
    <nav class="abas" aria-label="Situação">
        @foreach($situacoes as $chave => $rotulo)
            <a href="{{ $link(['situacao' => $chave, 'page' => null]) }}" @if($filtros['situacao'] === $chave) aria-current="page" @endif>{{ $rotulo }}</a>
        @endforeach
    </nav>
    <div class="filtro-servico">
        <input type="hidden" name="situacao" value="{{ $filtros['situacao'] }}">
        <label for="filtro-servico" class="visualmente-oculto">Serviço</label>
        <select id="filtro-servico" name="servico" onchange="this.form.submit()">
            <option value="">Todos os serviços</option>
            @foreach($servicos as $codigo => $s)
                <option value="{{ $codigo }}" @selected($filtros['servico'] === $codigo)>{{ $s['nome'] }}</option>
            @endforeach
        </select>
        <noscript><button type="submit" class="botao botao-secundario">Filtrar</button></noscript>
    </div>
</form>

<p class="muted contagem-fila" aria-live="polite">
    {{ $demandas->total() }} {{ $demandas->total() === 1 ? 'chamado' : 'chamados' }}
    @if(in_array($filtros['situacao'], ['abertos', 'atrasados'], true)) · prazo mais próximo primeiro @endif
</p>

<div class="fila">
@forelse($demandas as $demanda)
    @php $v = \App\Models\Demanda::VISUAL[$demanda->status]; $servico = $servicos[$demanda->servico]; @endphp
    <article class="cartao chamado">
        <header class="chamado-topo">
            <span class="servico-icone">@include('cidade.icone', ['nome' => $servico['icone']])</span>
            <div>
                <h2>{{ $servico['nome'] }}</h2>
                <a class="mono" href="{{ route('acompanhar', $demanda->protocolo) }}">{{ $demanda->protocolo }}</a>
            </div>
            <span class="etiquetas">
                <span class="pilula pilula-{{ $v['tom'] }}">@include('cidade.icone', ['nome' => $v['icone'], 'tamanho' => 20]) {{ \App\Models\Demanda::STATUS[$demanda->status] }}</span>
                @include('cidade._prazo', ['demanda' => $demanda])
            </span>
        </header>
        <p>{{ $demanda->descricao }}</p>
        <dl class="dados dados-compactos">
            <div><dt>Aberto em</dt><dd class="mono">{{ $demanda->created_at->format('d/m/Y') }}</dd></div>
            <div><dt>Prazo</dt><dd class="mono">{{ $demanda->prazo->format('d/m/Y') }}</dd></div>
            <div><dt>Local</dt><dd>{{ $demanda->endereco() ?? $demanda->latitude.', '.$demanda->longitude }}</dd></div>
            @if($demanda->veioDoSp156())<div><dt>Origem</dt><dd>Dados abertos SP156</dd></div>@endif
        </dl>
        @if($destino = \App\Models\Demanda::PROXIMO[$demanda->status] ?? null)
            <form method="post" action="{{ route('atualizar', $demanda->protocolo) }}">
                @csrf
                <input type="hidden" name="situacao" value="{{ $filtros['situacao'] }}">
                @if($filtros['servico'])<input type="hidden" name="servico" value="{{ $filtros['servico'] }}">@endif
                @if($demandas->currentPage() > 1)<input type="hidden" name="page" value="{{ $demandas->currentPage() }}">@endif
                <button class="botao {{ $destino === 'resolvida' ? 'botao-sucesso' : 'botao-primario' }}" name="status" value="{{ $destino }}">
                    @include('cidade.icone', ['nome' => \App\Models\Demanda::VISUAL[$destino]['icone'], 'tamanho' => 20])
                    {{ $destino === 'resolvida' ? 'Marcar como resolvida' : 'Iniciar atendimento' }}
                </button>
            </form>
        @endif
    </article>
@empty
    <div class="cartao vazio">
        @include('cidade.icone', ['nome' => 'entrada', 'tamanho' => 48])
        <p><strong>Nenhum chamado registrado{{ $filtros['situacao'] !== 'todos' || $filtros['servico'] ? ' com esses filtros' : '' }}.</strong></p>
        <p class="muted">Quando alguém abrir um chamado, ele aparece aqui. <a href="{{ route('novo') }}">Abrir um chamado de teste</a>.</p>
    </div>
@endforelse
</div>

@if($demandas->hasPages())
    <nav class="paginacao" aria-label="Páginas da fila">
        @if($demandas->onFirstPage())
            <span class="botao botao-secundario desativado" aria-disabled="true">Anterior</span>
        @else
            <a class="botao botao-secundario" href="{{ $demandas->previousPageUrl() }}" rel="prev">Anterior</a>
        @endif
        <span class="mono">Página {{ $demandas->currentPage() }} de {{ $demandas->lastPage() }}</span>
        @if($demandas->hasMorePages())
            <a class="botao botao-secundario" href="{{ $demandas->nextPageUrl() }}" rel="next">Próxima</a>
        @else
            <span class="botao botao-secundario desativado" aria-disabled="true">Próxima</span>
        @endif
    </nav>
@endif
@endsection
