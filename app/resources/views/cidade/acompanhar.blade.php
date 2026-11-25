@extends('cidade.layout')
@section('titulo', 'Acompanhar chamado')
@section('conteudo')
<div class="intro"><p class="rotulo">Acompanhamento</p><h1>Seu chamado</h1><p>Guarde o protocolo para voltar a esta consulta.</p></div>
<section class="cartao">
    <h2>{{ $servico['nome'] }}</h2>
    <dl class="dados"><div><dt>Protocolo</dt><dd><code>{{ $demanda->protocolo }}</code></dd></div>
    <div><dt>Prazo previsto</dt><dd>{{ $demanda->prazo->format('d/m/Y') }}</dd></div>
    <div><dt>Secretaria</dt><dd>{{ $servico['secretaria'] }}</dd></div></dl>
    <h3>Histórico de atendimento</h3>
    <ol class="historico">
        @foreach($historico as $evento)
        <li @if($loop->last) aria-current="step" @endif><strong>{{ \App\Models\Demanda::STATUS[$evento->status] }}</strong>
        <span class="muted">{{ \Illuminate\Support\Carbon::parse($evento->ocorrido_em)->format('d/m/Y H:i') }}</span></li>
        @endforeach
    </ol>
    <a href="{{ route('inicio') }}">Abrir outro chamado</a>
</section>
@endsection
