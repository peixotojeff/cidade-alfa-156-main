@extends('cidade.layout')
@section('titulo', 'Secretaria')
@section('conteudo')
<div class="intro"><p class="rotulo">Painel local · Demonstração</p><h1>Fila da secretaria</h1><p>{{ $simuladas }} notificações simuladas. Nenhuma mensagem real é enviada.</p></div>
@if(session('aviso'))<p class="aviso" role="status">{{ session('aviso') }}</p>@endif
<div class="pilha">
@forelse($demandas as $demanda)
    <article class="cartao">
        <p class="rotulo"><a href="{{ route('acompanhar', $demanda->protocolo) }}">{{ $demanda->protocolo }}</a></p>
        <h2>{{ $servicos[$demanda->servico]['nome'] }}</h2>
        <p>{{ $demanda->descricao }}</p>
        <p><strong>{{ \App\Models\Demanda::STATUS[$demanda->status] }}</strong> · Prazo {{ $demanda->prazo->format('d/m/Y') }}</p>
        <p class="muted">Local informado: {{ $demanda->latitude }}, {{ $demanda->longitude }}</p>
        @if($destino = \App\Models\Demanda::PROXIMO[$demanda->status] ?? null)
        <form method="post" action="{{ route('atualizar', $demanda->protocolo) }}">
            @csrf
            <button name="status" value="{{ $destino }}">{{ $destino === 'resolvida' ? 'Marcar resolvida' : 'Iniciar atendimento' }}</button>
        </form>
        @endif
    </article>
@empty
    <p class="cartao">Nenhum chamado registrado. <a href="{{ route('inicio') }}">Abra um chamado de teste</a>.</p>
@endforelse
</div>
@endsection
